-- ============================================================
--  WorkForce Manager — Oracle (PL/SQL) Database Schema
--  Converted from the original MySQL schema (database.sql).
--
--  Run as the application schema user, e.g. in SQL*Plus / SQL Developer:
--      @oracle_schema.sql
--
--  Auto-increment is emulated with SEQUENCE + BEFORE INSERT TRIGGER
--  (works on Oracle 11g and later). ENUMs become VARCHAR2 + CHECK.
-- ============================================================

-- ── Drop existing objects (ignore "does not exist" errors) ────
BEGIN
   FOR t IN (SELECT table_name FROM user_tables
             WHERE table_name IN ('NOTIFICATIONS','RATINGS','BOOKINGS','ADMINS','USERS','WORKERS')) LOOP
      EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS';
   END LOOP;
   FOR s IN (SELECT sequence_name FROM user_sequences
             WHERE sequence_name IN ('WORKERS_SEQ','USERS_SEQ','ADMINS_SEQ','BOOKINGS_SEQ','RATINGS_SEQ','NOTIFICATIONS_SEQ')) LOOP
      EXECUTE IMMEDIATE 'DROP SEQUENCE ' || s.sequence_name;
   END LOOP;
END;
/

-- ── WORKERS ───────────────────────────────────────────────────
CREATE TABLE workers (
    id            NUMBER         PRIMARY KEY,
    name          VARCHAR2(100)  NOT NULL,
    profession    VARCHAR2(100)  NOT NULL,
    skill         VARCHAR2(200)  DEFAULT '',
    experience    NUMBER(5)      DEFAULT 0,
    location      VARCHAR2(100)  DEFAULT '',
    phone         VARCHAR2(30)   UNIQUE,
    email         VARCHAR2(150)  UNIQUE,
    photo         VARCHAR2(255)  DEFAULT '',
    rating        NUMBER(3,1)    DEFAULT 0,
    rating_count  NUMBER(10)     DEFAULT 0,
    availability  VARCHAR2(20)   DEFAULT 'available'
                  CHECK (availability IN ('available','busy')),
    hourly_rate   NUMBER(10,2)   DEFAULT 0.00,
    password      VARCHAR2(255),               -- nullable: admin-added listings have no login
    approved      NUMBER(1)      DEFAULT 0,
    pending_edit  CLOB           DEFAULT NULL,
    pending_photo VARCHAR2(255)  DEFAULT NULL,
    created_at    TIMESTAMP      DEFAULT SYSTIMESTAMP
);

CREATE SEQUENCE workers_seq START WITH 1 INCREMENT BY 1 NOCACHE;
/
CREATE OR REPLACE TRIGGER workers_bi BEFORE INSERT ON workers FOR EACH ROW
WHEN (NEW.id IS NULL)
BEGIN
   SELECT workers_seq.NEXTVAL INTO :NEW.id FROM dual;
END;
/

-- ── USERS ─────────────────────────────────────────────────────
CREATE TABLE users (
    id         NUMBER         PRIMARY KEY,
    name       VARCHAR2(100)  NOT NULL,
    phone      VARCHAR2(30)   UNIQUE,
    email      VARCHAR2(150)  UNIQUE,
    location   VARCHAR2(100)  DEFAULT '',
    password   VARCHAR2(255)  NOT NULL,
    created_at TIMESTAMP      DEFAULT SYSTIMESTAMP
);

CREATE SEQUENCE users_seq START WITH 1 INCREMENT BY 1 NOCACHE;
/
CREATE OR REPLACE TRIGGER users_bi BEFORE INSERT ON users FOR EACH ROW
WHEN (NEW.id IS NULL)
BEGIN
   SELECT users_seq.NEXTVAL INTO :NEW.id FROM dual;
END;
/

-- ── ADMINS ────────────────────────────────────────────────────
CREATE TABLE admins (
    id         NUMBER         PRIMARY KEY,
    name       VARCHAR2(100)  NOT NULL,
    email      VARCHAR2(150)  UNIQUE NOT NULL,
    password   VARCHAR2(255)  NOT NULL,
    created_at TIMESTAMP      DEFAULT SYSTIMESTAMP
);

CREATE SEQUENCE admins_seq START WITH 1 INCREMENT BY 1 NOCACHE;
/
CREATE OR REPLACE TRIGGER admins_bi BEFORE INSERT ON admins FOR EACH ROW
WHEN (NEW.id IS NULL)
BEGIN
   SELECT admins_seq.NEXTVAL INTO :NEW.id FROM dual;
END;
/

-- ── BOOKINGS ──────────────────────────────────────────────────
CREATE TABLE bookings (
    id          NUMBER       PRIMARY KEY,
    user_id     NUMBER       NOT NULL,
    worker_id   NUMBER       NOT NULL,
    status      VARCHAR2(20) DEFAULT 'pending'
                CHECK (status IN ('pending','confirmed','awaiting_user','completed','cancelled')),
    booked_at   TIMESTAMP    DEFAULT SYSTIMESTAMP,
    CONSTRAINT fk_book_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_book_worker FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
);

CREATE SEQUENCE bookings_seq START WITH 1 INCREMENT BY 1 NOCACHE;
/
CREATE OR REPLACE TRIGGER bookings_bi BEFORE INSERT ON bookings FOR EACH ROW
WHEN (NEW.id IS NULL)
BEGIN
   SELECT bookings_seq.NEXTVAL INTO :NEW.id FROM dual;
END;
/

-- ── RATINGS ───────────────────────────────────────────────────
CREATE TABLE ratings (
    id         NUMBER     PRIMARY KEY,
    booking_id NUMBER     NOT NULL UNIQUE,
    user_id    NUMBER     NOT NULL,
    worker_id  NUMBER     NOT NULL,
    stars      NUMBER(1)  NOT NULL CHECK (stars BETWEEN 1 AND 5),
    rated_at   TIMESTAMP  DEFAULT SYSTIMESTAMP,
    CONSTRAINT fk_rate_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_rate_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_rate_worker  FOREIGN KEY (worker_id)  REFERENCES workers(id)  ON DELETE CASCADE
);

CREATE SEQUENCE ratings_seq START WITH 1 INCREMENT BY 1 NOCACHE;
/
CREATE OR REPLACE TRIGGER ratings_bi BEFORE INSERT ON ratings FOR EACH ROW
WHEN (NEW.id IS NULL)
BEGIN
   SELECT ratings_seq.NEXTVAL INTO :NEW.id FROM dual;
END;
/

-- ── NOTIFICATIONS ─────────────────────────────────────────────
CREATE TABLE notifications (
    id          NUMBER       PRIMARY KEY,
    target_role VARCHAR2(10) NOT NULL CHECK (target_role IN ('admin','worker','user')),
    target_id   NUMBER       NOT NULL,
    message     VARCHAR2(500) NOT NULL,
    link        VARCHAR2(300) DEFAULT '',
    is_read     NUMBER(1)     DEFAULT 0,
    created_at  TIMESTAMP     DEFAULT SYSTIMESTAMP
);

CREATE SEQUENCE notifications_seq START WITH 1 INCREMENT BY 1 NOCACHE;
/
CREATE OR REPLACE TRIGGER notifications_bi BEFORE INSERT ON notifications FOR EACH ROW
WHEN (NEW.id IS NULL)
BEGIN
   SELECT notifications_seq.NEXTVAL INTO :NEW.id FROM dual;
END;
/

COMMIT;
