USE praktikum_web_2401020025;

INSERT INTO program_studi (nama_prodi) VALUES
    ('Teknik Informatika'),
    ('Teknik Elektro');

INSERT INTO mahasiswa
    (nim, nama, email, usia, program_studi_id)
VALUES
    ('2401020025', 'Nurul Syafika',
     'nurul@gmail.com', 20, 1),
    ('2401020026', 'Pitria',
     'pitria@gmail.com', 21, 1),
    ('2401020007', 'Alfa Julyana',
     'alfa@gmail.com', 19, 2),
    ('2401020001', 'Calvin Ade Syahputra',
     'calvin@gmail.com', 22, 2);

UPDATE mahasiswa
SET email = 'nrlsyfika@gmail.com'
WHERE nim = '2401020025';

DELETE FROM mahasiswa
WHERE nim = '2401020001';

SELECT m.nim, m.nama, m.email, m.usia,
       p.nama_prodi
FROM mahasiswa AS m
JOIN program_studi AS p
    ON p.id = m.program_studi_id
ORDER BY m.nim;