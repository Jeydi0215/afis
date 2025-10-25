<?php $pageTitle = 'Login';
    // include 'components/loading.php';
    
    session_start();
    
    // Handle logout
    if(isset($_GET['logout'])) {
        session_unset();
        session_destroy();
        // Redirect to a clean URL
        header('Location: index.php');
        exit();
    }
    
    // Prevent browser from caching the page
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Check if the user is already logged in and redirect based on access level
    if (isset($_SESSION['user_id']) && isset($_SESSION['full_name']) && isset($_SESSION['access_level'])) {
        switch ($_SESSION['access_level']) {
            case 0:
                header('Location: pending.php');
                break;
            case 1:
                header('Location: /afis/dashboard.php');
                break;
            case 2:
                header('Location: /client/dashboard.php');
                break;
            case 3:
                header('Location: /user/dashboard.php');
                break;
            default:
                // If for some reason the access level is not 1, 2, or 3
                // You might want to log this or handle it differently
                header('Location: pending.php');
                break;
        }
        exit();
    }
    
    // Function to set active class on navigation for page title
    function setActiveClass($pageName) {    
        $currentPage = basename($_SERVER['PHP_SELF']);
        return $currentPage == $pageName ? 'active' : '';
    }
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="shortcut icon" type="image/jpg" href="assets/images/Archive-Favicon.png"/>
		<link rel="stylesheet" href="globals.css">
		<script src="https://unpkg.com/@tailwindcss/browser@4"></script>
		<title><?= isset($pageTitle) ? $pageTitle : 'Default Title' ?></title>
	</head>
	<body class="bg-mesh-gradient">
        <main class="flex overflow-hidden items-center justify-center" id="mainContent">
            <section class="flex justify-center items-center wide-container max-[840px]:flex-col max-[840px]:!w-full h-screen">
                <div class="flex flex-col justify-center gap-6 z-1 w-[475px]">
                    <div class="flex flex-col gap-30">
                        <div class="flex flex-col items-center gap-[10px] logo-wrapper">
                            <img src="assets/images/Stafify-Logo.png" alt="Stafify Logo" class="w-[205px]" onclick="window.location.href='index.php';" style="cursor: pointer;">
                            <p class="text-zinc-200 mt-2 text-center">Accounting & Finance Information System</p>
                        </div>
                        <div class="flex flex-col gap-[40px] bg-white !p-[40px] rounded-[20px]">
                            <div class="flex flex-col gap-3">
                                <h1 class="text-3xl text-dark text-left mt-5 !font-bold">User Login</h1>
                                <p class="text-zinc-600 mt-2">Please enter your credentials below.</p>
                            </div>
                            <form class="flex flex-col gap-7" action="sign-in-function.php" method="post" autocomplete="off">
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-3">
                                        <input type="text" class="!px-4 !py-3 border-1 border-zinc-400 rounded-[7px]" name="uid" id="uid" placeholder="Username or Email" required>
                                        
                                        <div class="relative">
                                            <input type="password" class="!px-4 !py-3 pr-12 border-1 border-zinc-400 rounded-[7px] password-field w-full" name="password" id="pass" placeholder="Password" required>
                                            <button type="button" onclick="togglePassword('pass')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-sm text-zinc-600">Show</button>
                                        </div>
                                    </div>
                                    <a href="#" class="text-accent text-right text-sm font-500">Forgot Password?</a>
                                </div>
                                
                                <div class="flex gap-4">
                                    <button type="submit" class="primary-button w-full">Sign In</button>
                                </div>
                            </form>
                            <p class="text-center text-zinc-600">Don't have an account yet? <a href="sign-up.php" class="text-accent font-500">Sign Up</a></p>
                        </div>
                        <p class="copyright text-center text-white animate__animated animate__fadeInUp delay-2s">©<span class="yearDisplay">Loading...</span> Stafify BPO. All rights reserved</p>
                    </div>
                </div>
            </section>
        </main>

        <script>
            function togglePassword(fieldId) {
                const input = document.getElementById(fieldId);
                const btn = input.nextElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.textContent = 'Hide';
                } else {
                    input.type = 'password';
                    btn.textContent = 'Show';
                }
            }
            
            // Get the current year
            document.addEventListener("DOMContentLoaded", function() {
                document.querySelector(".yearDisplay").textContent = new Date().getFullYear();
            });
        </script>
    </body>
</html>