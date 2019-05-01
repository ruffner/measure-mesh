CREATE DATABASE logger;

USE logger;

CREATE USER 'logger'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON logger.* TO 'logger'@'localhost';

CREATE TABLE data_log (
	pkey INTEGER NOT NULL AUTO_INCREMENT, 
	time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	temps VARCHAR(64) NOT NULL, 
	mtype VARCHAR(10) NOT NULL,  
	stype VARCHAR(10), 
		PRIMARY KEY(pkey)) 
		ENGINE=INNODB;

CREATE TABLE params (
       pkey  INTEGER NOT NULL AUTO_INCREMENT, 
       id    VARCHAR(20) NOT NULL, 
       val   VARCHAR(64) NOT NULL, 
       stype VARCHAR(10) NOT NULL, 
       	     PRIMARY KEY(pkey))
	     ENGINE=INNODB;
