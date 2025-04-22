# King County Elections Database Tool

**For Docker command-line interface to work, Docker Desktop must be running**

This README.md file is located at `.../docker/README.md`. The following is how I
suggest that the repository be used:

* Extract your text files, and put them in `.../docker/web/data/*`
* Make sure you have docker installed,
    <https://docs.docker.com/desktop/setup/install/windows-install/>
* In your command line, navigate to `.../docker` and run (the `$` is just the
prompt, run what comes after that)

- `$ docker compose up -d --build` [the `--build` is optional if you have already
    used it once]

**WHEN DOWNLOADING NEW .TXT DATA FILES**

- `$ docker compose exec web bash`

You are now inside of the docker container, and should be brought to a directory
named `/var/www/html`. Let us start by loading the voter database. Run the
following commands - in each, something like `{thing}` indicates a placehold.
Here, the terminal prompt is also going to be different - I set up the docker
environment so that the prompt should include the current location, so that
the paths are clear.

## Loading the data

Start by loading composer dependencies:

- `root@/var/www/html# composer install --no-dev`

### Loading voters

- `root@/var/www/html# php scripts/ConvertToSql.php --source {data/KI.txt} --target {data/voters.sql} --type voters --from-blank`

This will
- take data from the file at `data/KI.txt`
- treat is as an export of voter information (instead of voting history)
- transform it, and put the resulting sql in `data/voters.sql`
- that sql will create the database table from scratch (`--from-blank`)

Then, to apply that SQL file, run

- `root@/var/www/html# php scripts/SqlRunner.php --source {data/voters.sql}`

Which will apply the sql file, and create a temporary table with the information
(temporary in the sense of not going to be around forever, not temporary in the
SQL sense of a temporary table, it is a real table). Finally, we want to
replace the current version of the data with the temporary one just loaded,
via

- `root@/var/www/html# php scripts/ApplyTempTable.php --type voters`

This will replace any existing voter database with the temporary one created
above.

### Loading voter history

Voting history will be a *tiny* bit different, since it comes in multiple files,
and when we load the second we don't want to delete all of the things that
we loaded from the first. Use the following commands, assuming that you want
to load from 2 files in `data/first.txt` and `data/second.txt` - if you want
to load from more than 2 files, follow the same procedure as was used for the
second, i.e. omit the `--from-blank`

- `root@/var/www/html# php scripts/ConvertToSql.php --source {data/first.txt} --target {data/hist-1.sql} --type history --from-blank`

This will
- take data from the file at `data/first.txt`
- treat is as an export of voting history information (instead of voters)
- transform it, and put the resulting sql in `data/hist-1.sql`
- that sql will create the database table from scratch (`--from-blank`)

For each additional set of voting history information you want to use, run
a command like
- `root@/var/www/html# php scripts/ConvertToSql.php --source {data/second.txt} --target {data/hist-2.sql} --type history`

that does **NOT** have `--from-blank.

Next, apply each of these SQL files to create the temporary table. **WARNING**
the history that was created with `--from-blank` needs to be first, otherwise
either things will break.

- `root@/var/www/html# php scripts/SqlRunner.php --source {data/hist-1.sql}`
- `root@/var/www/html# php scripts/SqlRunner.php --source {data/hist-2.sql}`

Finally, after applying each of the history files to the temporary table,
convert that to be the actual table with

- `root@/var/www/html# php scripts/ApplyTempTable.php --type history`

To escape from the docker container and get back to your computer, run
- `root@/var/www/html# exit`

## Examining the data -- EACH TIME YOU WANT TO RUN A QUERY

Having populated the database, you can now connect to the `db` docker container
so that the data can be queried. On your computer, run in `.../docker`

* `$ docker compose exec db bash`

[If you get an error message that a service is not running, run the
`docker compose up -d` command; see the start of this document for reference]

In the database container, the terminal prompt is `bash-4.4#` (you might have
different numbers) by default, but we won't be here long. To connect to the
database, run

* `bash-4.4# mysql -proot ki_elections_db`

And now you can run your queries! The tables to look at are `ki_voters` and
`ki_voter_history`, e.g. to count registered voters who live on Mercer Island:

```
mysql> SELECT COUNT(*) FROM ki_voters WHERE RegCity = 'Mercer Island'\G
*************************** 1. row ***************************
COUNT(*): 19740
1 row in set (3.96 sec)
```

To get from the MySQL shell back to the docker container shell, just run
- `mysql> exit`

and then to exit that and get back to your computer:

- `bash-4.4# exit`

## Shutting down

To turn off the docker stack, in the `.../docker` directory, run
`$ docker compose down`

## Notes

To identify duplicates (just `uniq -d` doesn't work because it looks for
adjacent duplicates, need to sort): `cat data/foo.txt | sort | uniq -d`