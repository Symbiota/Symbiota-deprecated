### Symbiota in Docker
This docker image packages Symbiota and all of its dependencies with
easy-to-use configuration logic. All symbiota configuration can be done
through the manipulation of yaml files located in /usr/local/etc/symbiota
inside the container.

The symbiota source is located at /var/www/symbiota inside the container.
Any further configuration changes that are desired can be made in this location,
either by mounting it on the Docker host or by editing the files within the
container itself.

---

To build:
```
docker build -t symbiota .
```

***Example docker-compose.yml***<br>

```
version: '3.7'

services:
  db:
    image: mariadb
    container_name: symbiota_db
    environment:
      MYSQL_ROOT_PASSWORD: password
    volumes:
      - symbiota_data:/var/lib/mysql

  symbiota:
    image: symbiota
    container_name: symbiota_frontend
    volumes:
      - symbiota_config:/usr/local/etc/symbiota
    ports:
      - 80:80

volumes:
  symbiota_data:
    external: true
  symbiota_config:
    external: true
```

In the above example the following steps are used to get symbiota up and running:
1. docker-compose up -d
2. Connect the the symbiota db server using `docker exec -it symbiota_db mysql -u root --password=password`
and run the following commands:
  - Create the symbiota database: `CREATE DATABASE symbiota;`
  - Add the readonly user:
    ```
    CREATE USER 'readonly' identified by 'password';
    GRANT SELECT on symbiota.* to 'readonly'@'%' identified by 'password';
    ```
  - Add the readwrite user:
    ```
    CREATE USER 'readwrite' identified by 'password';
    GRANT SELECT,INSERT,UPDATE,DELETE on symbiota.* to 'readwrite'@'%' identified by 'password';
    ```
4. Configure the database schema via the instructions in [Symbiota Repo]/docs/INSTALL.txt.
5. Edit /usr/local/etc/symbiota/database.yml to match the new databse configuration
by mounting the symbiota_config volume into a container with a text editor:<br>
`docker run --rm --volumes-from symbiota_frontend -it debian:stable-slim /bin/bash -c "apt-get update; apt-get install vim -y; vim /usr/local/etc/symbiota/database.yml"`:

```
database:
  host: db
  port: 3306
  name: symbiota
  users:
    readonly:
      username: readonly
      password: password
    readwrite:
      username: readwrite
      password: password
```

