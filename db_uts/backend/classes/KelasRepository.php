<?php

class KelasRepository
{
    public function __construct(private PDO $conn) {}

    public function getAll(): array
    {
        return $this->conn->query('SELECT * FROM kelas ORDER BY id_kelas')->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM kelas WHERE id_kelas = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $nama, ?string $deskripsi, int $kapasitas): void
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO kelas (nama_kelas, deskripsi, kapasitas) VALUES (?, ?, ?)'
        );
        $stmt->execute([$nama, $deskripsi, $kapasitas]);
    }

    public function update(int $id, string $nama, ?string $deskripsi, int $kapasitas): void
    {
        $kelas = $this->getById($id);
        if (!$kelas) {
            throw new DomainException('Kelas tidak ditemukan.');
        }
        if ($kapasitas < (int) $kelas['jumlah_daftar']) {
            throw new DomainException(
                "Kapasitas tidak boleh lebih kecil dari jumlah peserta saat ini ({$kelas['jumlah_daftar']})."
            );
        }

        $stmt = $this->conn->prepare(
            'UPDATE kelas SET nama_kelas = ?, deskripsi = ?, kapasitas = ? WHERE id_kelas = ?'
        );
        $stmt->execute([$nama, $deskripsi, $kapasitas, $id]);
    }

    public function delete(int $id): void
    {
        try {
            $stmt = $this->conn->prepare('DELETE FROM kelas WHERE id_kelas = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {   // masih direferensikan tabel pendaftaran
                throw new DomainException('Kelas tidak bisa dihapus karena sudah memiliki data pendaftaran.');
            }
            throw $e;
        }
    }
}