<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');
$mysqli=new mysqli("localhost","root","","harmony");
if($mysqli->connect_error){
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>"Conexión fallida"]);
    exit;
}
$sql="SELECT fecha,tipoA,tipoB,tipoC,tipoD,contingencia AS cont,resp_ie AS respIE,resp_asoc AS respAsoc,resp_int AS respInt FROM registros_frs ORDER BY fecha ASC";
$res=$mysqli->query($sql);
if(!$res){
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$mysqli->error]);
    exit;
}
$datos=[];
while($r=$res->fetch_assoc()){
    $r['cont']=(bool)$r['cont'];
    $datos[]=$r;
}
echo json_encode(['status'=>'success','datos'=>$datos], JSON_UNESCAPED_UNICODE);
$mysqli->close();
