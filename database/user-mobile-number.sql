ALTER TABLE users
    ADD COLUMN mobile_number varchar(16)
        CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    ADD CONSTRAINT chk_user_mobile_number CHECK
        (mobile_number IS NULL OR
         mobile_number REGEXP '^[+][1-9][0-9]{1,14}$');
