-- Create the course notes table for Academic Dashboard
CREATE TABLE IF NOT EXISTS mdl_local_acad_course_notes (
  id BIGINT(10) NOT NULL AUTO_INCREMENT,
  courseid BIGINT(10) NOT NULL,
  title VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,
  timecreated BIGINT(10) NOT NULL,
  timemodified BIGINT(10) NOT NULL,
  PRIMARY KEY (id),
  KEY courseid_idx (courseid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores notes for each course in the Academic Dashboard';
