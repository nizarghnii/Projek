<?php

class MahasiswaRepository
{
    public function __construct(private PDO $conn) {}

    public function getAll(): array
    {
        $sql = "SELECT m.*, u.username,
                       (SELECT COUNT(*) FROM pendaftaran p
                         WHERE p.id_mahasiswa = m.id_mahasiswa
                           AND p.status = 'terdaftar') AS kelas_aktif
                FROM mahasiswa m
                JOIN users u ON u.id_user = m.id_user
                ORDER BY m.id_mahasiswa";
        return $this->conn->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT m.*, u.username
             FROM mahasiswa m JOIN users u ON u.id_user = m.id_user
             WHERE m.id_mahasiswa = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, string $nim, string $nama, string $email): void
    {
        if (!$this->getById($id)) {
            throw new DomainException('Data mahasiswa tidak ditemukan.');
        }

        try {
            $stmt = $this->conn->prepare(
                'UPDATE mahasiswa SET nim = ?, nama = ?, email = ? WHERE id_mahasiswa = ?'
            );
            $stmt->execute([$nim, $nama, $email, $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new DomainException('NIM atau email sudah dipakai mahasiswa lain.');
            }
            throw $e;
        }
    }

    /** Hapus mahasiswa + akun users-nya secara atomic. */
    public function delete(int $id): void
    {
        $mhs = $this->getById($id);
        if (!$mhs) {
            throw new DomainException('Data mahasiswa tidak ditemukan.');
        }

        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM pendaftaran WHERE id_mahasiswa = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new DomainException('Mahasiswa tidak bisa dihapus karena memiliki data pendaftaran.');
        }

        try {
            $this->conn->beginTransaction();

            $this->conn->prepare('DELETE FROM mahasiswa WHERE id_mahasiswa = ?')->execute([$id]);
            $this->conn->prepare('DELETE FROM users WHERE id_user = ?')->execute([$mhs['id_user']]);

            $this->conn->commit();
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}