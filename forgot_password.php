<?php
session_start();

// Database configuration - same as login.php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "nisai_db";

// Initialize variables
$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST['email']);
    
    // Validate form data
    if (empty($email)) {
        $error_message = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Create connection
            $conn = new mysqli($servername, $db_username, $db_password, $dbname);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Check if email exists in database
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Generate a unique reset token
                $reset_token = bin2hex(random_bytes(32));
                $expiry_date = date("Y-m-d H:i:s", strtotime("+1 hour"));
                
                // Store the token in the database
                $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?");
                $update_stmt->bind_param("sss", $reset_token, $expiry_date, $email);
                
                if ($update_stmt->execute()) {
                    // In a real application, you would send an email here with a link to reset_password.php?token=$reset_token
                    // For this example, we'll just show a success message
                    $success_message = "Password reset instructions have been sent to your email address.";
                } else {
                    $error_message = "Error generating reset token. Please try again.";
                }
                
                $update_stmt->close();
            } else {
                $error_message = "No account found with that email address.";
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $error_message = "Database error: " . $e->getMessage();
            // Log the full error for debugging (remove in production)
            error_log($e->getMessage());
        }
    }
}
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Forgot Password</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Datta Able is trending dashboard template made using Bootstrap 5 design framework. Datta Able is available in Bootstrap, React, CodeIgniter, Angular,  and .net Technologies."
    />
    <meta
      name="keywords"
      content="Bootstrap admin template, Dashboard UI Kit, Dashboard Template, Backend Panel, react dashboard, angular dashboard"
    />
    <meta name="author" content="CodedThemes" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="assets/images/logo_report_icon.png" type="image/x-icon" />
    <!-- [Font] Family -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <!-- [phosphor Icons] https://phosphoricons.com/ -->
    <link rel="stylesheet" href="assets/fonts/phosphor/duotone/style.css" />
    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="assets/css/style.css" id="main-style-link" />
  </head>
  <!-- [Head] end -->
       <script>
      // Function to get theme from localStorage or default to light
      function getStoredTheme() {
        return localStorage.getItem('theme') || 'light';
      }
      
      // Function to set theme in localStorage
      function setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
      }
      
      // Apply theme on page load
      document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = getStoredTheme();
        document.documentElement.setAttribute('data-pc-theme', savedTheme);
        
        // Update the theme icon in the header
        const themeIcon = document.querySelector('.pc-h-item [data-feather="sun"], .pc-h-item [data-feather="moon"]');
        if (themeIcon) {
          themeIcon.setAttribute('data-feather', savedTheme === 'dark' ? 'moon' : 'sun');
        }
      });
    </script>
  <!-- [Body] Start -->

  <body>
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
      <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
        <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <div class="auth-main relative">
      <div class="auth-wrapper v1 flex items-center w-full h-full min-h-screen">
        <div class="auth-form flex items-center justify-center grow flex-col min-h-screen relative p-6 ">
          <div class="w-full max-w-[350px] relative">
            <div class="auth-bg ">
              <span class="absolute top-[-100px] right-[-100px] w-[300px] h-[300px] block rounded-full bg-theme-bg-1 animate-[floating_7s_infinite]"></span>
              <span class="absolute top-[150px] right-[-150px] w-5 h-5 block rounded-full bg-primary-500 animate-[floating_9s_infinite]"></span>
              <span class="absolute left-[-150px] bottom-[150px] w-5 h-5 block rounded-full bg-theme-bg-1 animate-[floating_7s_infinite]"></span>
              <span class="absolute left-[-100px] bottom-[-100px] w-[300px] h-[300px] block rounded-full bg-theme-bg-2 animate-[floating_9s_infinite]"></span>
            </div>
            <div class="card sm:my-12  w-full shadow-none">
              <div class="card-body !p-10">
                <div class="text-center mb-8">
                  <a href="login.php"><img src="assets/images/logo_report.png" alt="img" width=100px;/></a>
                </div>
                
                <?php if (!empty($error_message)): ?>
                  <div class="alert alert-danger mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                  </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                  <div class="alert alert-success mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                  </div>
                <?php endif; ?>
                
                <h4 class="text-center font-medium mb-4">Reset Your Password</h4>
                <p class="text-center text-muted mb-4">Enter your email address and we'll send you instructions to reset your password.</p>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                  <div class="mb-4">
                    <input type="email" name="email" class="form-control" id="floatingInput" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                  </div>
                  <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary mx-auto shadow-2xl">Send Reset Instructions</button>
                  </div>
                </form>
                <div class="flex justify-between items-end flex-wrap mt-4">
                  <h6 class="font-medium mb-0">Remember your password?</h6>
                  <a href="login.php" class="text-primary-500">Back to Login</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- [ Main Content ] end -->
    <!-- Required Js -->
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/icon/custom-icon.js"></script>
    <script src="assets/js/plugins/feather.min.js"></script>
    <script src="assets/js/component.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/script.js"></script>

    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
    </div>

    <script>
      // Modified layout_change function to store theme preference
      function layout_change(theme) {
        if (theme === 'false') {
          // Use the current theme if 'false' is passed (default behavior)
          theme = document.documentElement.getAttribute('data-pc-theme');
        }
        setStoredTheme(theme);
        document.documentElement.setAttribute('data-pc-theme', theme);
        
        // Update the theme icon
        const themeIcon = document.querySelector('.pc-h-item [data-feather="sun"], .pc-h-item [data-feather="moon"]');
        if (themeIcon) {
          themeIcon.setAttribute('data-feather', theme === 'dark' ? 'moon' : 'sun');
          // Re-initialize Feather icons
          if (typeof feather !== 'undefined') {
            feather.replace();
          }
        }
      }
      
      // Modified layout_change_default function
      function layout_change_default() {
        const defaultTheme = 'light'; // Set your default theme here
        layout_change(defaultTheme);
      }
      
      // Initialize with stored theme
      const savedTheme = getStoredTheme();
      layout_change(savedTheme);
    </script>
     
    <script>
      layout_theme_sidebar_change('dark');
    </script>
    
     
    <script>
      change_box_container('false');
    </script>
     
    <script>
      layout_caption_change('true');
    </script>
     
    <script>
      layout_rtl_change('false');
    </script>
     
    <script>
      preset_change('preset-1');
    </script>
     
    <script>
      main_layout_change('vertical');
    </script>
  </body>
  <!-- [Body] end -->
</html>