<?php
   function write_ligne( $raw, $sep="/", $nmaxcar=80) {
        // insere un saut de ligne apres le 80 caracter 
		$nbcar=strlen($raw);
		if ($nbcar>$nmaxcar){
			$s1=substr( $raw,0,$nmaxcar);
			$pos1=strrpos($s1,$sep);
			if ($pos1>0){
				$s1=substr( $raw,0,$pos1);
				$s2=substr( $raw,$pos1+1);
			}
			else {
				$s1=substr( $raw,0,$nmaxcar);
				$s2=substr( $raw,$nmaxcar);
			}
		    return $s1."<br />".$s2;
		}
		else{
			return $raw;
		}
    }
	
$s= "A.1.1/A.1.2 /A.1.3/A.1.4/A.1.5/A.2.1/A.2.2/A.2.3/A.3.1/A.3.2/A.3.3/A.3.4/B.1.1/B.1.2/B.1.3/B.2.1/B.2.2/B.2.3/B.2.4/B.3.1/B.3.2/B.3.3/B.3.4/B.3.5/B.4.1/B.4.2/B.4.3/ ";
echo "<html><head><title>TEST</title></head><body>\n";
echo "<br>$s<br>\n";
echo "<br>".write_ligne( $s, "/",100)."\n";
echo "</body></html>\n";
?>