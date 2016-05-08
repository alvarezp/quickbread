# Quickbread

Quickbread aims to be a generic UI for PostgreSQL databases. It will
dynamically read the database definition and display the data in a
user-friendly format.

The project is in very early development stage. It supports the following
features:

* Tabular view
  - Multiple-column sorting.

* Record details view

Be patient. It will slowly support more features. The immediate next step is
to support the full set of CRUD operations. This, itself, depends on having
good "widget" set to let the user edit data according to the underlying data
type.


# Install overview

1. Unpack the program in a Document Root for your Webserver. Quickbread uses
   absolute paths sometimes, so it will not work if you place it just in
   any place. I recommend setting up an exlusive HTTP port for Quickbread.
   
2. Import the ___qb1.sql file into your database, like this:

   ```psql databasename < ___qb1.sql```

3. Make sure PostgreSQL is listening on localhost port 5432.

4. Visit the URL where you dropped Quickbread. You will be asked a username
   and a password. Use any user name you have as user name and login to the
   database using any valid login credentials for your database.

If I'm missing anything, please raise a Github issue so we can document it
and track its progress into fixing or whatever resolution becomes appropriate.

# Conventions used by Quickbread

This list will hold the design conventions that Quickbread will use from the
DB. The fundamental one is that it will try to take the best from the current
database definition, but there are some aspectes that fall outside of SQL DDL
itself.

* Login credentials are the same as for the database. In fact, the login just
  gets passed on to PostgreSQL. If it succeeds, the user is logged in. All
  subsequent operations are done under the same credentials.
  
* More to come...


# Design principles

* I will try really hard to prevent Quickbread from using any client-side
Javascript. In fact, the user is encouraged to disable JavaScript for the
Quickbread installation location.
