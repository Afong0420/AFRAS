-- AFRAs Supabase PostgreSQL Schema
-- Run this in Supabase SQL Editor: https://supabase.com/dashboard/project/vksdulfntdhilgpcmalh/sql

-- Drop tables if re-running
DROP TABLE IF EXISTS attendance CASCADE;
DROP TABLE IF EXISTS unit_schedule CASCADE;
DROP TABLE IF EXISTS unit CASCADE;
DROP TABLE IF EXISTS students CASCADE;
DROP TABLE IF EXISTS venue CASCADE;
DROP TABLE IF EXISTS course CASCADE;
DROP TABLE IF EXISTS lecture CASCADE;
DROP TABLE IF EXISTS faculty CASCADE;
DROP TABLE IF EXISTS admin CASCADE;

-- admin
CREATE TABLE admin (
    "Id"           SERIAL PRIMARY KEY,
    "firstName"    VARCHAR(50)  NOT NULL,
    "lastName"     VARCHAR(50)  NOT NULL,
    "emailAddress" VARCHAR(50)  NOT NULL,
    "password"     VARCHAR(250) NOT NULL
);

-- faculty
CREATE TABLE faculty (
    "Id"             SERIAL PRIMARY KEY,
    "facultyName"    VARCHAR(255) NOT NULL,
    "facultyCode"    VARCHAR(50)  NOT NULL,
    "dateRegistered" DATE         NOT NULL
);

-- lecture
CREATE TABLE lecture (
    "Id"           SERIAL PRIMARY KEY,
    "firstName"    VARCHAR(255) NOT NULL,
    "lastName"     VARCHAR(255) NOT NULL,
    "emailAddress" VARCHAR(255) NOT NULL,
    "password"     VARCHAR(255) NOT NULL,
    "phoneNo"      VARCHAR(50)  NOT NULL,
    "facultyCode"  VARCHAR(50)  NOT NULL,
    "dateCreated"  VARCHAR(50)  NOT NULL
);

-- course
CREATE TABLE course (
    "Id"          SERIAL PRIMARY KEY,
    "name"        VARCHAR(50) NOT NULL,
    "facultyID"   INT         NOT NULL,
    "dateCreated" DATE        NOT NULL,
    "courseCode"  VARCHAR(50) NOT NULL
);

-- venue
CREATE TABLE venue (
    "Id"             SERIAL PRIMARY KEY,
    "className"      VARCHAR(50) NOT NULL,
    "facultyCode"    VARCHAR(50) NOT NULL,
    "currentStatus"  VARCHAR(50) NOT NULL,
    "capacity"       INT         NOT NULL,
    "classification" VARCHAR(50) NOT NULL,
    "dateCreated"    DATE        NOT NULL
);

-- unit
CREATE TABLE unit (
    "Id"           SERIAL PRIMARY KEY,
    "name"         VARCHAR(50)  NOT NULL,
    "unitCode"     VARCHAR(50)  NOT NULL,
    "courseID"     VARCHAR(50)  NOT NULL,
    "dateCreated"  DATE         NOT NULL,
    "startTime"    TIME         DEFAULT NULL,
    "endTime"      TIME         DEFAULT NULL,
    "venueID"      INT          DEFAULT NULL,
    "lectureID"    INT          DEFAULT NULL,
    "scheduleDays" VARCHAR(50)  DEFAULT NULL
);

-- unit_schedule
CREATE TABLE unit_schedule (
    "Id"        SERIAL PRIMARY KEY,
    "unitId"    INT         NOT NULL REFERENCES unit("Id") ON DELETE CASCADE,
    "day"       VARCHAR(3)  NOT NULL CHECK ("day" IN ('Mon','Tue','Wed','Thu','Fri')),
    "session"   VARCHAR(10) NOT NULL DEFAULT 'morning',
    "startTime" TIME        NOT NULL,
    "endTime"   TIME        NOT NULL,
    "venueID"   INT         DEFAULT NULL,
    UNIQUE ("unitId", "day", "session")
);

-- students
CREATE TABLE students (
    "Id"                 SERIAL PRIMARY KEY,
    "firstName"          VARCHAR(255) NOT NULL,
    "lastName"           VARCHAR(255) NOT NULL,
    "registrationNumber" VARCHAR(255) NOT NULL UNIQUE,
    "email"              VARCHAR(50)  NOT NULL,
    "faculty"            VARCHAR(10)  NOT NULL,
    "courseCode"         VARCHAR(20)  NOT NULL,
    "studentImage"       VARCHAR(300) NOT NULL,
    "dateRegistered"     VARCHAR(50)  NOT NULL,
    "yearLevel"          VARCHAR(20)  NOT NULL,
    "section"            VARCHAR(5)   NOT NULL
);

-- attendance
CREATE TABLE attendance (
    "attendanceID"               SERIAL PRIMARY KEY,
    "studentRegistrationNumber"  VARCHAR(100) NOT NULL,
    "course"                     VARCHAR(100) NOT NULL,
    "attendanceStatus"           VARCHAR(100) NOT NULL,
    "dateMarked"                 DATE         NOT NULL,
    "unit"                       VARCHAR(100) NOT NULL,
    "session"                    VARCHAR(20)  NOT NULL DEFAULT '',
    "timeMarked"                 TIME         DEFAULT NULL,
    "session_end_time"           TIME         DEFAULT NULL,
    "lecturer_id"                INT          DEFAULT NULL
);

-- Seed admin
INSERT INTO admin ("firstName","lastName","emailAddress","password")
VALUES ('Admin','','admin@gmail.com','$2y$10$FIBqWvTOXRMoQOAB2FBz3uUbaCwRYTM1zQreFI6i/7v6Qi8y9R1i6');

-- Seed faculty
INSERT INTO faculty ("facultyName","facultyCode","dateRegistered")
VALUES ('Information Technology','College of Computer Education','2026-03-11');

-- Seed course
INSERT INTO course ("name","facultyID","dateCreated","courseCode")
VALUES ('Information Technology',20,'2026-03-11','BSIT');

-- Seed venue
INSERT INTO venue ("className","facultyCode","currentStatus","capacity","classification","dateCreated") VALUES
('Basic Lab A','College of Computer Education','available',50,'laboratory','2026-03-11'),
('Basic Lab B','College of Computer Education','available',50,'laboratory','2026-03-11'),
('IT Lab','College of Computer Education','available',50,'laboratory','2026-03-11'),
('Room 113','College of Computer Education','available',50,'class','2026-03-11');
