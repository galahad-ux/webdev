<?php
class Dbh
{
    private $host = 'mysql-momo-vacation.alwaysdata.net';
    private $db   = 'momo-vacation_bdd';
    private $user = 'momo-vacation_website';
    private $pass = 'FhR^?emJ8h4xzF]c?NCDE]j0@8QC:RJqvN3.rB;NsrBE]a<43R1BAuR@uBGB*K@H';
    private $charset = 'utf8mb4'; // Recommandé pour gérer correctement tous les caractères (accents, emojis...)
    private $options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourne les résultats sous forme de tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false,]   ;               // Améliore la sécurité des requêtes préparées


    public function connect()
    {
        try {
            $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
            $dbh = new PDO($dsn, $this->user, $this->pass, $this->options);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $dbh;
        }
        catch (PDOException $e) {
            error_log("Erreur de connexion BDD : " . $e->getMessage());
            die("Désolé, une erreur technique est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.");
        }
    }
}
?>