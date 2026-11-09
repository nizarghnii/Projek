<?php

class PendaftaranService
{
    public function __construct(private PDO $conn) {}

    /** Semua kelas + status pendaftaran terakhir milik mahasiswa ini. */
    public function getKelasUntukMahasiswa(int $idMahasiswa): array
    {
        $stmt = $this->conn->prepare(
            "SELECT k.*,
                    (SELECT p.status FROM pendaftaran p
                      WHERE p.id_kelas = k.id_kelas AND p.id_mahasiswa = ?
                      ORDER BY p.id_pendaftaran DESC LIMIT 1) AS status_saya
             FROM kelas k
             ORDER BY k.id_kelas"
        );
        $stmt->execute([$idMahasiswa]);
        return $stmt->fetchAll();
    }

    public function getByMahasiswa(int $idMahasiswa): array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*, k.nama_kelas
             FROM pendaftaran p
             JOIN kelas k ON k.id_kelas = p.id_kelas
             WHERE p.id_mahasiswa = ?
             ORDER BY p.tanggal_daftar DESC, p.id_pendaftaran DESC"
        );
        $stmt->execute([$idMahasiswa]);
        return $stmt->fetchAll();
    }

    /** Untuk admin: seluruh pendaftaran. */
    public function getAll(): array
    {
        return $this->conn->query(
            "SELECT p.*, k.nama_kelas, m.nama, m.nim
             FROM pendaftaran p
             JOIN kelas k     ON k.id_kelas = p.id_kelas
             JOIN mahasiswa m ON m.id_mahasiswa = p.id_mahasiswa
             ORDER BY p.tanggal_daftar DESC, p.id_pendaftaran DESC"
        )->fetchAll();
    }

    /** Daftar kelas: insert/aktifkan pendaftaran + tambah jumlah_daftar, atomic. */
    public function daftar(int $idMahasiswa, int $idKelas): void
    {
        try {
            $this->conn->beginTransaction();

            // 1. Kunci baris kelas supaya dua orang tidak merebut kursi terakhir bersamaan
            $stmt = $this->conn->prepare(
                'SELECT kapasitas, jumlah_daftar FROM kelas WHERE id_kelas = ? FOR UPDATE'
            );
            $stmt->execute([$idKelas]);
            $kelas = $stmt->fetch();

            if (!$kelas) {
                throw new DomainException('Kelas tidak ditemukan.');
            }
            if ((int) $kelas['jumlah_daftar'] >= (int) $kelas['kapasitas']) {
                throw new DomainException('Kelas sudah penuh.');
            }

            // 2. Cek riwayat pendaftaran mahasiswa ini di kelas yang sama
            $stmt = $this->conn->prepare(
                'SELECT id_pendaftaran, status FROM pendaftaran
                 WHERE id_mahasiswa = ? AND id_kelas = ?
                 ORDER BY id_pendaftaran DESC LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$idMahasiswa, $idKelas]);
            $lama = $stmt->fetch();

            if ($lama && $lama['status'] === 'terdaftar') {
                throw new DomainException('Kamu sudah terdaftar di kelas ini.');
            }

            // 3. Daftar baru, atau aktifkan kembali yang pernah dibatalkan
            if ($lama) {
                $this->conn->prepare(
                    "UPDATE pendaftaran
                     SET status = 'terdaftar', tanggal_daftar = CURRENT_TIMESTAMP
                     WHERE id_pendaftaran = ?"
                )->execute([$lama['id_pendaftaran']]);
            } else {
                $this->conn->prepare(
                    "INSERT INTO pendaftaran (id_mahasiswa, id_kelas, status)
                     VALUES (?, ?, 'terdaftar')"
                )->execute([$idMahasiswa, $idKelas]);
            }

            // 4. Sinkronkan counter di tabel kelas
            $this->conn->prepare(
                'UPDATE kelas SET jumlah_daftar = jumlah_daftar + 1 WHERE id_kelas = ?'
            )->execute([$idKelas]);

            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /** Batal kelas: ubah status + kurangi jumlah_daftar, atomic. */
    public function batal(int $idMahasiswa, int $idKelas): void
    {
        try {
            $this->conn->beginTransaction();

            // Urutan kunci sama dengan daftar() (kelas dulu, lalu pendaftaran) supaya tidak deadlock
            $stmt = $this->conn->prepare('SELECT 1 FROM kelas WHERE id_kelas = ? FOR UPDATE');
            $stmt->execute([$idKelas]);
            if (!$stmt->fetchColumn()) {
                throw new DomainException('Kelas tidak ditemukan.');
            }

            $stmt = $this->conn->prepare(
                "SELECT id_pendaftaran FROM pendaftaran
                 WHERE id_mahasiswa = ? AND id_kelas = ? AND status = 'terdaftar'
                 FOR UPDATE"
            );
            $stmt->execute([$idMahasiswa, $idKelas]);
            $idPendaftaran = $stmt->fetchColumn();

            if (!$idPendaftaran) {
                throw new DomainException('Pendaftaran aktif tidak ditemukan.');
            }

            $this->conn->prepare(
                "UPDATE pendaftaran SET status = 'dibatalkan' WHERE id_pendaftaran = ?"
            )->execute([$idPendaftaran]);

            $this->conn->prepare(
                'UPDATE kelas SET jumlah_daftar = jumlah_daftar - 1
                 WHERE id_kelas = ? AND jumlah_daftar > 0'
            )->execute([$idKelas]);

            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}