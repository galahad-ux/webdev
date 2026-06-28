<?php
$_fl = $_SESSION['language'] ?? 'fr';
$_ft = [
    'fr' => [
        'about'   => 'À propos', 'careers' => 'Carrières', 'press'    => 'Presse',
        'support' => 'Support',  'contact' => 'Nous contacter',
        'legal'   => 'Légal',    'terms'   => 'CGU', 'legal_notice' => 'Mentions légales',
        'help'    => 'Centre d\'aide', 'rights' => 'Tous droits réservés.',
    ],
    'en' => [
        'about'   => 'About Us', 'careers' => 'Careers', 'press'    => 'Press',
        'support' => 'Support',  'contact' => 'Contact Us',
        'legal'   => 'Legal',    'terms'   => 'Terms of Service', 'legal_notice' => 'Legal Notice',
        'help'    => 'Help Center', 'rights' => 'All rights reserved.',
    ],
][$_fl];
?>
        <footer>
            <div class="footer-content">
                <div class="footer-col">
                    <h4>Momo Travel</h4>
                    <ul>
                        <li><a href="#"><?= $_ft['about'] ?></a></li>
                        <li><a href="#"><?= $_ft['careers'] ?></a></li>
                        <li><a href="#"><?= $_ft['press'] ?></a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4><?= $_ft['support'] ?></h4>
                    <ul>
                        <li><a href="contact"><?= $_ft['contact'] ?></a></li>
                        <li><a href="faq">FAQ</a></li>
                        <li><a href="forum">Forum</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4><?= $_ft['legal'] ?></h4>
                    <ul>
                        <li><a href="cgu"><?= $_ft['terms'] ?></a></li>
                        <li><a href="mentions"><?= $_ft['legal_notice'] ?></a></li>
                        <li><a href="faq"><?= $_ft['help'] ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Momo Travel. <?= $_ft['rights'] ?></p>
            </div>
        </footer>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const cookieBanner = document.getElementById("cookie-banner");
                const acceptBtn = document.getElementById("accept-cookies");
                const declineBtn = document.getElementById("decline-cookies");

                const cookieConsent = localStorage.getItem("momo_cookie_consent");

                if (!cookieConsent && cookieBanner) {
                    setTimeout(() => { cookieBanner.classList.add("show"); }, 1000);
                }

                if (acceptBtn) {
                    acceptBtn.addEventListener("click", function() {
                        localStorage.setItem("momo_cookie_consent", "accepted");
                        cookieBanner.classList.remove("show");
                    });
                }

                if (declineBtn) {
                    declineBtn.addEventListener("click", function() {
                        localStorage.setItem("momo_cookie_consent", "declined");
                        cookieBanner.classList.remove("show");
                    });
                }
            });
        </script>
    </body>
</html>
