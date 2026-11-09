<?php

class UserRepository
{
    public function __construct(private PDO $conn) {}

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function findMahasiswaIdByUser(int $idUser): ?int
    {
        $stmt = $this->conn->prepare('SELECT id_mahasiswa FROM mahasiswa WHERE id_user = ?');
        $stmt->execute([$idUser]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** Cek apakah password yang sama sudah dipakai user lain. */
    private function isPasswordUsed(string $password): bool
    {
        $hashes = $this->conn->query('SELECT password FROM users')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($hashes as $hash) {
            if (password_verify($password, $hash)) {
                return true;
            }
        }
        return false;
    }

    /** Insert users + mahasiswa secara atomic. */
    public function registerMahasiswa(
        string $username, string $password,
        string $nim, string $nama, string $email
    ): void {
        if ($this->isPasswordUsed($password)) {
            throw new DomainException('Password sudah digunakan pengguna lain, pilih password yang berbeda.');
        }

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                'INSERT INTO users (username, password) VALUES (?, ?)'
            );
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $idUser = (int) $this->conn->lastInsertId();

            $stmt = $this->conn->prepare(
                'INSERT INTO mahasiswa (id_user, nim, nama, email) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$idUser, $nim, $nama, $email]);

            $this->conn->commit();
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            if ($e->getCode() === '23000') {   // pelanggaran UNIQUE
                throw new DomainException('Username, NIM, atau email sudah terdaftar.');
            }
            throw $e;
        }
    }
}