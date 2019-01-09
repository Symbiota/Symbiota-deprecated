### Symbiota in Docker
This docker image packages Symbiota and all of its dependencies with
easy-to-use configuration logic. All symbiota configuration can be done
through the manipulation of one file, /usr/local/etc/symbiota/symbiota.yml
inside the container. Symbiota is automatically restarted when any changes
to the files in /usr/local/etc/symbiota are detected.

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
    environment:
      MYSQL_ROOT_PASSWORD: password
    volumes:
      - symbiota_data:/var/lib/mysql
    ports:
      - 3306:3306

  symbiota:
    image: symbiota
    container_name: symbiota
    volumes:
      - symbiota_config:/usr/local/etc/symbiota
    ports:
      - 80:80

volumes:
  symbiota_data:
  symbiota_config:
```

In the above example the following steps are used to get symbiota up and running:
1. docker-compose up -d
2. symbiota database is created via `mysql -u root --password='password' -h 127.0.0.1 -e "CREATE DATABASE symbiota;"`
3. readonly and readwrite users are added to the database:
  - `mysql -u root --password='password' -h 127.0.0.1 -e "CREATE USER 'readonly' identified by 'password';"`
  - `mysql -u root --password='password' -h 127.0.0.1 -e "CREATE USER 'readwrite' identified by 'password';"`
  - `mysql -u root --password='password' -h 127.0.0.1 -e "GRANT SELECT on symbiota.* to 'readonly'@'%' identified by 'password';"`
  - `mysql -u root --password='password' -h 127.0.0.1 -e "GRANT SELECT,INSERT,UPDATE,DELETE on symbiota.* to 'readwrite'@'%' identified by 'password';"`
  - `mysql -u root --password='password' -h 127.0.0.1 -e "FLUSH privileges;"`
4. The database schema is configured via the instructions in [Symbiota Repo]/docs/INSTALL.txt.
5. /usr/local/etc/symbiota/symbiota.yml is updated to match the new databse configuration
by mounting the symbiota_config volume into a container with a text editor:<br>
`docker run --rm --volumes-from symbiota -it debian:stable-slim /bin/bash -c "apt-get update; apt-get install vim -y; vim /usr/local/etc/symbiota/symbiota.yml"`
6. The container detects any modified files and automatically updates itself.
