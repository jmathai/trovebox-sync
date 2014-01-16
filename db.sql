CREATE TABLE `meta` (
  `id` VARCHAR( 255 ) NOT NULL ,
  `data` TEXT NOT NULL ,
  `hash` VARCHAR( 40 ) NOT NULL ,
  UNIQUE (
    `id`
  )
); 
