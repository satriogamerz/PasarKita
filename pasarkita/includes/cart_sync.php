<?php
// Sinkronisasi keranjang sesi dengan database untuk pengguna yang sudah login.
function simpanKeranjangAkun(mysqli $conn, int $idUser, array $keranjang): void {
    $up = $conn->prepare("INSERT INTO cart_items (id_user, id_produk, jumlah) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE jumlah = VALUES(jumlah), updated_at = CURRENT_TIMESTAMP");
    $del = $conn->prepare("DELETE FROM cart_items WHERE id_user=? AND id_produk=?");
    foreach ($keranjang as $idProduk => $jumlah) {
        $idProduk = (int)$idProduk; $jumlah = (int)$jumlah;
        if ($idProduk > 0 && $jumlah > 0) { $up->bind_param("iii", $idUser, $idProduk, $jumlah); $up->execute(); }
        else { $del->bind_param("ii", $idUser, $idProduk); $del->execute(); }
    }
    $up->close(); $del->close();
}
function gabungKeranjangAkun(mysqli $conn, int $idUser, array $keranjangSesi): array {
    $stmt=$conn->prepare("SELECT id_produk, jumlah FROM cart_items WHERE id_user=?");
    $stmt->bind_param("i",$idUser); $stmt->execute(); $res=$stmt->get_result();
    $gabung=[]; while($r=$res->fetch_assoc()) $gabung[(int)$r['id_produk']]=(int)$r['jumlah']; $stmt->close();
    foreach($keranjangSesi as $id=>$qty) $gabung[(int)$id]=max((int)($gabung[(int)$id]??0),(int)$qty);
    // Hanya produk aktif dan batasi jumlah berdasarkan stok.
    $hasil=[];
    foreach($gabung as $id=>$qty){
        $q=$conn->prepare("SELECT stok FROM products WHERE id_produk=? AND status='aktif' LIMIT 1");
        $q->bind_param("i",$id); $q->execute(); $row=$q->get_result()->fetch_assoc(); $q->close();
        if($row && (int)$row['stok']>0 && $qty>0) $hasil[$id]=min($qty,(int)$row['stok']);
    }
    simpanKeranjangAkun($conn,$idUser,$hasil);
    return $hasil;
}
?>