CREATE USER readonly IDENTIFIED BY 'password';
CREATE USER readwrite IDENTIFIED BY 'password';

GRANT select ON symbiota.* TO readonly@'%';
GRANT select,insert,update,delete ON symbiota.* TO readwrite@'%';

