# King County Elections Database Tool

## Installing

* `git clone https://github.com/DanielS6/KingCountyElections.git`
* Make sure you have docker installed,
    <https://docs.docker.com/desktop/setup/install/windows-install/>

This README.md file is located at `.../docker/README.md`. The following is how I
suggest that the repository be used:

* Extract your text files, and put them in `.../docker/web/data/*`
* In your command line, navigate to `.../docker` and run (the `$` is just the
prompt, run what comes after that)
* Run `$ docker compose up -d --build` [`--build` optional after first use]

**WHEN DOWNLOADING NEW .TXT DATA FILES**

- `$ docker compose exec web bash` - this gets you into the docker container,
and then run:
- `root@/var/www/html# composer install --no-dev` to install dependencies, and
- `root@/var/www/html# php scripts/ConvertToSql.php --source {data/KI.txt} --target {data/voters.sql} --type voters --from-blank`

This will convert the *voter information* file at `data/KI.txt` into SQL at
`data/voters.sql`. Then, to apply that SQL file, run the following commands in
order:

1. `root@/var/www/html# php scripts/SqlRunner.php --source {data/voters.sql}`
2. `root@/var/www/html# php scripts/ApplyTempTable.php --type voters`

Together, they will load the SQL from `data/voters.sql` into a temporary table
and then replace the real table with that temporary table.

### Loading voter history

Voting history will be a *tiny* bit different, since it comes in multiple files,
and when we load the second we don't want to delete all of the things that
we loaded from the first. Use the following commands, assuming that you want
to load from 2 files in `data/first.txt` and `data/second.txt` - if you want
to load from more than 2 files, follow the same procedure as was used for the
second, i.e. omit the `--from-blank`.

- `root@/var/www/html# php scripts/ConvertToSql.php --source {data/first.txt} --target {data/hist-1.sql} --type history --from-blank`

This will convert the *voter htistory* file at `data/first.txt` into SQL at
`data/hist-1.sql`.

For each additional set of voting history information you want to use, run
a command like
- `root@/var/www/html# php scripts/ConvertToSql.php --source {data/second.txt} --target {data/hist-2.sql} --type history`

that does **NOT** have `--from-blank`.

Next, run the following commands in this order. The history file that was
created with `--from-blank` needs to be first; add an extra `SqlRunner.php` call
for each history file. The `ApplyTempTable.php` must be last.

1. `root@/var/www/html# php scripts/SqlRunner.php --source {data/hist-1.sql}`
2. `root@/var/www/html# php scripts/SqlRunner.php --source {data/hist-2.sql}`
3. [any more history files]
4. `root@/var/www/html# php scripts/ApplyTempTable.php --type history`

To escape from the docker container, just use `exit`

## Examining the data -- EACH TIME YOU WANT TO RUN A QUERY

Having populated the database, you can now connect to the `db` docker container
so that the data can be queried. On your computer, run in `.../docker`

* `$ docker compose exec db mysql -proot ki_elections_db`

[If you get an error message that a service is not running, run the
`docker compose up -d` command; see the start of this document for reference]

And now you can run your queries! The tables to look at are `ki_voters` and
`ki_voter_history`, e.g. to count registered voters who live on Mercer Island:

```
mysql> SELECT COUNT(*) FROM ki_voters WHERE RegCity = 'Mercer Island'\G
*************************** 1. row ***************************
COUNT(*): 19740
1 row in set (3.96 sec)
```

To get from the MySQL shell back to your computer, run `exit`.

## Shutting down

To turn off the docker stack, in the `.../docker` directory, run
`$ docker compose down`
