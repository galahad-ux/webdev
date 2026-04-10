        <footer>
            <div class="footer-content">
                <div class="footer-col">
                    <h4>Momo Travel</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="contact">Contact Us</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Cancellations and Refunds</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Legal Notice</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Momo Travel. All rights reserved.</p>
            </div>
        </footer>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const cookieBanner = document.getElementById("cookie-banner");
                const acceptBtn = document.getElementById("accept-cookies");
                const declineBtn = document.getElementById("decline-cookies");

                // 1. On vérifie si l'utilisateur a déjà fait un choix précédemment
                const cookieConsent = localStorage.getItem("momo_cookie_consent");

                if (!cookieConsent) {
                    // 2. Si aucun choix n'a été fait, on affiche la bannière avec une petite animation
                    setTimeout(() => {
                        cookieBanner.classList.add("show");
                    }, 1000); // Apparaît après 1 seconde
                }

                // 3. Action si l'utilisateur accepte
                acceptBtn.addEventListener("click", function() {
                    localStorage.setItem("momo_cookie_consent", "accepted");
                    cookieBanner.classList.remove("show");
                    
                    // C'est ici que tu activerais tes trackers (ex: Google Analytics)
                    console.log("Cookies acceptés ! Lancement des scripts de suivi.");
                });

                // 4. Action si l'utilisateur refuse
                declineBtn.addEventListener("click", function() {
                    localStorage.setItem("momo_cookie_consent", "declined");
                    cookieBanner.classList.remove("show");
                    
                    // Ici on ne fait rien, on respecte son choix
                    console.log("Cookies refusés.");
                });
            });
        </script>
    </body>
</html>