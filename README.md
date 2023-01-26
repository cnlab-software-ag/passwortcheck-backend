# Backend API for Passwortcheck

## Content
- The files included in this package resemble the backend api part of the [Passwortcheck](https://github.com/cnlab-software-ag/passwortcheck).
- The backend api currently provides three endpoints: one for submitting results, and two separate endpoints to query statistical information about recorded results.
- **/results/index.php**: Endpoint to upload the result of a password check.
- **/statistics/index.php**
  - Endpoint providing statistics about performed checks in four different intervals (last day, last week, last month, last year).
  - Example of data
    ```
    {
      "checks": {
        "last24Hours": {
          "id": "last24Hours",
          "checkTotal": 0,
          "data": [0, 0]
        },
        "last7Days": {
          "id": "last7Days",
          "checkTotal": 99,
          "data": [74, 25]
        },
        "last30Days": {
          "id": "last30Days",
          "checkTotal": 99,
          "data": [74, 25]
        },
        "last12Months": {
          "id": "last12Months",
          "checkTotal": 99,
          "data": [74, 25]
        }
      }
    }
    ```
- **/timeseries/index.php**
  - Endpoint providing statistics about performed checks as a time series for the last year. 
  - Example of data
    ```
    {
      "checks": {
        "2022-01-10": {
          "id": "2022-01-10",
          "data": [0, 0],
          "checkTotal": 0
        },
        "2022-01-09": {
          "id": "2022-01-09",
          "data": [0, 0],
          "checkTotal": 0
        },
        "2022-01-08": {
          "id": "2022-01-08",
          "data": [1, 0],
          "checkTotal": 1
        },
        "2022-01-07": {
          "id": "2022-01-07",
          "data": [17, 0],
          "checkTotal": 17
        },
        "2022-01-06": {
          "id": "2022-01-06",
          "data": [28, 4],
          "checkTotal": 32
        },
        ...
        "2021-01-11": {
          "id": "2021-01-11",
          "data": [0, 0],
          "checkTotal": 0
        }
      }
    }
    ```
- The data object in the results can be interpreted as follows:
  ```
  [<number of weak results>, <number of strong results>]
  ```


## Preconditions
- The backend functionality is implemented using PHP, therefore PHP support must be enabled for the used web server.
- Results are persisted in a database to provide some statistics on performed checks. The current implementation is using MySQL, therfore MySQL must be installed (or a compatible product such as MariaDB).
- There are no special features used (neither from PHP nor from MySQL), therefore any version should work. However, it is suggested to use the most recent version of both products.


## Database

### Supported databases
- The current implementation is meant to store data in a MySQL database. 
- Adaptions to a different database engine (eg. MSSQL, Postgres) should be possible with very little changes.

### Tables
The DB schema consists of two tables, one for storing results, one for tracking recent activities.

#### Table "results"
- This table stores the results of performed password checks. It only stores a timestamp and the result of a check ("strong", "weak"), but not further details.
- Statement to create the table
  ```
  CREATE TABLE `results` (
    `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `result` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`id`)
  )
  ```

#### Table "activites"
- This table is used to record past activites in order to prevent "spamming" the database with too many results.
- Prior to storing the result, the code checks whether there was already a result submission within a certain time (currently 5 seconds) from the same source IP address.
  - If not, the result is stored in the database, and the activity is recorded.
  - If there was a recent result upload, the result is not stored.
- The activity table only holds activities up to one day, older entries are automatically removed.
- Statement to create the table
  ```
  CREATE TABLE `activities` (
    `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
  )
  ```


## Configuration
- In order to get the backend API working properly, some configuration settings are required.
- All configuration parameter can be defined in environement variables, the PHP scripts will automatically use the settings.
- See the file `env.sample` for a sample configuration.
- The following environment variables need to be defined:
  - **DB_HOST**: specifies the host where the database is located. This can be `localhost`, a IP address or a DNS hostname
  - **DB_NAME**: specifies the schema name of the database.
  - **DB_USERNAME**: specifies the user name that shall be used to access the DB.
  - **DB_PASSWORD**: specifies the password for accessing the database.
  - **ALLOWED_ORIGINS**: lists a origins that are allowed to access the api in a comma-separated list. Origins that are not on the list will be blocked from accessing the api.






