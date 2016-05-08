DROP SCHEMA ___qb1 CASCADE;
CREATE SCHEMA ___qb1;
GRANT USAGE ON SCHEMA ___qb1 TO PUBLIC;

CREATE OR REPLACE FUNCTION ___qb1.prettify(varchar) RETURNS varchar AS $$
	SELECT upper("substring"(snc.snc, 1, 1)) || "substring"(snc.snc, 2)
           FROM ( SELECT btrim(replace($1::text, '_'::text, ' '::text)) AS snc) snc
$$ LANGUAGE SQL;
GRANT EXECUTE ON FUNCTION ___qb1.prettify(varchar) TO public;


CREATE VIEW ___qb1.database AS
SELECT
  current_catalog AS name,
  quote_ident(current_catalog) AS identifier,
  ___qb1.prettify(current_catalog::text) AS pretty_name,
  pg_catalog.shobj_description(d.oid, 'pg_database') AS description
FROM
  pg_catalog.pg_database d
WHERE
  datname = current_catalog;
GRANT SELECT ON ___qb1.database TO public;


CREATE FUNCTION ___qb1.recent_interaction(varchar, varchar) RETURNS varchar AS $$
BEGIN
	PERFORM
        table_name
    FROM
        information_schema.tables
    WHERE
        table_name = '___hints_for_entities';

	IF NOT FOUND THEN
        RETURN 'table';
    END IF;

    SELECT
        interaction
    FROM
        ___hints_for_entities
    WHERE
        schema = $1 AND entity = $2;
END
$$ LANGUAGE plpgsql;
GRANT EXECUTE ON FUNCTION ___qb1.recent_interaction(varchar, varchar) TO public;


CREATE VIEW ___qb1.entities AS
 SELECT
    nc.nspname::information_schema.sql_identifier AS schema,
    c.relname::information_schema.sql_identifier AS name,
    quote_ident(c.relname) AS identifier,
    ___qb1.prettify(c.relname::text) AS pretty_name,
    NULL::text AS singular_name,
    obj_description((quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname))::regclass, 'pg_class')::text as description,
    has_any_column_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'SELECT'::text) AS can_select,
    has_any_column_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'INSERT'::text) AS can_insert,
    has_any_column_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'UPDATE'::text) AS can_update,
    has_table_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'DELETE'::text) AS can_delete,
    has_table_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'TRUNCATE'::text) AS can_truncate,
    ___qb1.recent_interaction(nc.nspname::text, c.relname::text) AS recent_interaction
 FROM
    pg_namespace nc
    JOIN pg_class c ON nc.oid = c.relnamespace
 WHERE
    c.relkind = 'r'
	AND nc.nspname NOT IN ('pg_catalog', 'information_schema')
	AND NOT nc.nspname LIKE '\_\_%'
	AND NOT c.relname LIKE '\_\_%'
	AND (
        has_any_column_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'SELECT'::text)
        OR has_any_column_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'INSERT'::text)
        OR has_any_column_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'UPDATE'::text)
        OR has_table_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'DELETE'::text)
        OR has_table_privilege(quote_ident(nc.nspname) || '.'::text || quote_ident(c.relname), 'TRUNCATE'::text)
	);
GRANT SELECT ON ___qb1.entities TO public;


CREATE VIEW ___qb1.attributes AS
SELECT
 table_schema AS entity_schema,
 table_name AS entity_name,
 column_name AS name,
 (SELECT upper(substring(snc from 1 for 1)) || substring(snc.snc from 2) FROM (SELECT trim(replace(cols.column_name, '_', ' ')) AS snc) AS snc)::text AS pretty_name,
 quote_ident(column_name) as identifier,
 col_description((cols.table_schema::text || '.'::text || cols.table_name::text)::regclass, cols.ordinal_position) AS description,
 data_type,
 is_nullable = 'NO' AND column_default IS NULL AS required,
 column_default AS default,
 has_column_privilege(cols.table_schema::text || '.'::text || cols.table_name::text, column_name, 'SELECT'::text) AS can_select,
 has_column_privilege(cols.table_schema::text || '.'::text || cols.table_name::text, column_name, 'INSERT'::text) AS can_insert,
 has_column_privilege(cols.table_schema::text || '.'::text || cols.table_name::text, column_name, 'UPDATE'::text) AS can_update
FROM
 information_schema.columns AS cols
 INNER JOIN ___qb1.entities AS entities ON cols.table_schema = entities.schema AND cols.table_name = entities.name
;
GRANT SELECT ON ___qb1.attributes TO public;


CREATE VIEW ___qb1.filters AS
 SELECT
    current_database()::information_schema.sql_identifier AS filter_catalog,
    nc.nspname::information_schema.sql_identifier AS filter_schema,
    (( SELECT regexp_matches(pg_get_viewdef(c.oid)::text, '\n   FROM (.*)\n  WHERE'::text) AS regexp_matches))[1] AS filter_table,
    c.relname::information_schema.sql_identifier AS filter_name,
    obj_description(c.oid, 'pg_class'::name) AS description,
    (( SELECT regexp_matches(pg_get_viewdef(c.oid)::text, '\n   FROM .*\n  WHERE (.*);$'::text) AS regexp_matches))[1] AS filter_where,
    'VIEW_SELECT_FROM_SINGLE_TABLE' AS filter_deduction_type
   FROM
    pg_namespace nc,
    pg_class c
  WHERE
    c.relnamespace = nc.oid
    AND c.relkind = 'v'::"char"
    AND NOT pg_is_other_temp_schema(nc.oid)
    AND (pg_has_role(c.relowner, 'USAGE'::text)
          OR has_table_privilege(c.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)
          OR has_any_column_privilege(c.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text))
    AND nc.nspname::text NOT IN ('pg_catalog'::text, 'information_schema'::text)
	AND NOT nc.nspname LIKE '\_\_%'
	AND NOT c.relname LIKE '\_\_%'
    AND ((( SELECT regexp_matches(pg_get_viewdef(c.oid)::text, '\n   FROM (.*)\n  WHERE'::text) AS regexp_matches))[1] IN ( SELECT tables.table_name
           FROM information_schema.tables));
GRANT SELECT ON ___qb1.filters TO public;

CREATE VIEW ___qb1.entity_attributes AS
SELECT
  current_catalog AS catalog,
  ns.nspname AS schema,
  c.relname AS entity,
  quote_ident(c.relname) AS entity_identifier,
  a.attname AS name,
  ___qb1.prettify(a.attname::character varying) AS pretty_name,
  col_description(c.oid, a.attnum) AS description,
  a.attnum AS ordinal_position,
  tns.nspname AS data_type_schema,
  t.typname AS data_type_name,
  CASE
    WHEN t.typelem <> 0::oid AND t.typlen = (-1) THEN 'ARRAY'::text
    WHEN tns.nspname = 'pg_catalog'::name THEN format_type(t.oid, NULL::integer)
    ELSE 'USER-DEFINED'::text
  END AS base_type,
  a.attnotnull AS "notnull",
  a.attnotnull AND NOT a.atthasdef AS required,
  false AS is_calculated,
  quote_ident(a.attname) AS select_expression,
  has_column_privilege(c.oid, a.attnum, 'SELECT'::text) AS can_select,
  has_column_privilege(c.oid, a.attnum, 'UPDATE'::text) AS can_update,
  has_column_privilege(c.oid, a.attnum, 'INSERT'::text) AS can_insert,
  pg_get_expr(ad.adbin, ad.adrelid)::information_schema.character_data AS default_expression,
  CASE WHEN a.attnum = ANY(pk.conkey) THEN pk.conname END AS pk_name
FROM
  pg_attribute a
    LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum
    LEFT JOIN pg_class c ON a.attrelid = c.oid
      LEFT JOIN pg_constraint pk ON pk.conrelid = c.oid,
  pg_namespace ns,
  pg_type t,
  pg_namespace tns
WHERE
  c.relnamespace = ns.oid
  AND a.atttypid = t.oid
  AND t.typnamespace = tns.oid
  -- AND pk.contype = 'p'
  AND NOT ns.nspname LIKE '\_\_%'
  AND NOT c.relname LIKE '\_\_%'
  AND NOT ns.nspname IN ('pg_catalog', 'information_schema', 'pg_toast')
  AND NOT a.attisdropped
  AND a.attnum > 0
  AND c.relkind = 'r'
UNION ALL
SELECT
  current_catalog AS catalog,
  targns.nspname AS schema,
  targ.typname AS entity,
  quote_ident(targ.typname) AS entity_identifier,
  p.proname AS name,
  ___qb1.prettify(p.proname::character varying) AS pretty_name,
  obj_description(p.oid) AS description,
  NULL::integer AS ordinal_position,
  tretns.nspname AS data_type_schema,
  tret.typname AS data_type_name,
  CASE
    WHEN tret.typelem <> 0::oid AND tret.typlen = (-1) THEN 'ARRAY'::text
    WHEN tretns.nspname = 'pg_catalog'::name THEN format_type(tret.oid, NULL::integer)
    ELSE 'USER-DEFINED'::text
  END::information_schema.character_data AS base_type,
  NULL::boolean AS "notnull",
  NULL::boolean AS required,
  true AS is_calculated,
  quote_ident(ns.nspname) || '.' || quote_ident(p.proname) || '(' || quote_ident(targ.typname) || '.*)' AS select_expression,
  has_function_privilege(p.oid, 'EXECUTE') AS can_select,
  false AS can_update,
  false AS can_insert,
  NULL::character varying AS default_expression,
  NULL::character varying AS pk_name
FROM
  pg_proc p,
  pg_namespace ns,
  pg_type tret,
  pg_type targ,
  pg_namespace targns,
  pg_namespace tretns,
  pg_class c
WHERE
  p.pronamespace = ns.oid
  AND p.pronargs = 1
  AND p.prorettype = tret.oid
  AND p.proargtypes[0] = targ.oid
  AND targ.typnamespace = targns.oid
  AND tretns.oid = tret.typnamespace
  AND targ.typtype = 'c'
  AND targ.typrelid = c.oid
  AND c.relkind = 'r'
  AND ns.nspname NOT IN ('pg_catalog', 'information_schema')
  AND p.provolatile = 's'
  AND p.proname LIKE '\_\_cf\_%'
  AND NOT ns.nspname LIKE '\_\_%'
  AND NOT p.proname LIKE '\_\_%';
GRANT SELECT ON ___qb1.entity_attributes TO public;

CREATE VIEW ___qb1.entity_actions AS
SELECT
  current_database() AS catalog,
  targns.nspname AS schema,
  targ.typname AS entity,
  quote_ident(targ.typname) AS entity_identifier,
  p.proname AS name,
  ___qb1.prettify(p.proname::character varying) AS pretty_name,
  obj_description(p.oid) AS description,
  NULL::integer AS ordinal_position,
  tretns.nspname AS data_type_schema,
  tret.typname AS data_type_name,
  CASE
    WHEN tret.typelem <> 0::oid AND tret.typlen = (-1) THEN 'ARRAY'::text
    WHEN tretns.nspname = 'pg_catalog'::name THEN format_type(tret.oid, NULL::integer)
    ELSE 'USER-DEFINED'::text
  END::information_schema.character_data AS base_type,
  quote_ident(ns.nspname) || '.' || quote_ident(p.proname) || '(' || quote_ident(targ.typname) || '.*)' AS select_expression,
  has_function_privilege(p.oid, 'EXECUTE') AS can_execute
FROM
  pg_proc p,
  pg_namespace ns,
  pg_type tret,
  pg_type targ,
  pg_namespace targns,
  pg_namespace tretns,
  pg_class c
WHERE
  p.pronamespace = ns.oid
  AND p.pronargs = 1
  AND p.prorettype = tret.oid
  AND p.proargtypes[0] = targ.oid
  AND targ.typnamespace = targns.oid
  AND tretns.oid = tret.typnamespace
  AND targ.typtype = 'c'
  AND targ.typrelid = c.oid
  AND c.relkind = 'r'
  AND ns.nspname NOT IN ('pg_catalog', 'information_schema')
  AND NOT p.proname LIKE '\_\_cf\_%'
  AND NOT ns.nspname LIKE '\_\_%'
  AND NOT p.proname LIKE '\_\_%';
GRANT SELECT ON ___qb1.entity_actions TO public;

CREATE VIEW ___qb1.deamix AS
  SELECT
    'D' AS object_type,
    NULL::varchar AS schema,
    name,
    identifier,
    pretty_name,
    description,
    NULL::varchar AS recent_interaction,
    NULL::integer AS ordinal_position,
    NULL::varchar AS entity,
    NULL::varchar AS entity_identifier,
    NULL AS data_type_schema,
    NULL::varchar AS base_type,
    NULL::bool AS notnull,
    NULL::bool AS required,
    NULL::bool AS is_calculated,
    NULL::bool AS can_select,
    NULL::bool AS can_update,
    NULL::bool AS can_insert,
    NULL::bool AS can_delete,
    NULL::varchar AS default_expression,
    NULL::varchar AS pk_name,
    NULL::varchar AS select_expression
  FROM
    ___qb1.database
UNION ALL
  SELECT
    'E' AS object_type,
    schema,
    name,
    identifier,
    pretty_name,
    description,
    recent_interaction,
    NULL::integer AS ordinal_position,
    NULL::varchar AS entity,
    NULL::varchar AS entity_identifier,
    NULL::varchar AS data_type_schema,
    NULL::varchar AS base_type,
    NULL::bool AS notnull,
    NULL::bool AS required,
    NULL::bool AS is_calculated,
    can_select,
    can_update,
    can_insert,
    can_delete,
    NULL::varchar AS default_expression,
    NULL::varchar AS pk_name,
    NULL::varchar AS select_expression
  FROM
    ___qb1.entities
UNION ALL
  SELECT
    'A' AS object_type,
    schema,
    name,
    CASE WHEN is_calculated THEN select_expression ELSE NULL::varchar END AS identifier,
    pretty_name,
    description,
    NULL::varchar AS recent_interaction,
    ordinal_position,
    entity,
    entity_identifier,
    data_type_schema,
    base_type,
    "notnull",
    required,
    is_calculated,
    can_select,
    can_update,
    can_insert,
    NULL::bool AS can_delete,
    default_expression,
    pk_name,
    select_expression
  FROM
    ___qb1.entity_attributes
;
GRANT SELECT ON ___qb1.deamix TO public;
    

