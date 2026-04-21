<?php
include_once "Dbh.php";
class Login extends Dbh
{
    public function checkEmail($email){
        this->connect();
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $this->prepare("SELECT user_id FROM user WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
    $step = 2; // Existe -> Login
}

else {
    $step = 3; // N'existe pas -> Register
}
} else {
    $error_message = "Veuillez entrer une adresse e-mail valide.";
}
}
}