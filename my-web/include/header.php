<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->

  <head>
    <title>Nisai Pashion Store</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="description"
      content="Nisai Pashion Store - Admin Dashboard"
    />
    <meta
      name="keywords"
      content="admin template, dashboard, bootstrap 5, php, mysql"
    />
    <meta name="author" content="Nisai Pashion Store" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="../assets/images/logo_report_icon.png" type="image/x-icon" />

     <!-- [Font] Family -->
     <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- [Icon Libraries] -->
    <!-- [phosphor Icons] https://phosphoricons.com/ -->
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 

    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script> -->

    <style>
      .theme-toggle {
        cursor: pointer;
        border: none;
        background: none;
        padding: 0.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
      }
      
      .theme-toggle:hover {
        background-color: rgba(0,0,0,0.05);
      } 
      
      [data-pc-theme="dark"] .theme-toggle:hover {
        background-color: rgba(255, 255, 255, 1);
      }
      
      .theme-icon {
        width: 20px;
        height: 20px;
        transition: opacity 0.3s ease;
      }
      
      .light-icon {
        display: block;
      }
      
      .dark-icon {
        display: none;
      }
      
      [data-pc-theme="dark"] .light-icon {
        display: none;
      }
      
      [data-pc-theme="dark"] .dark-icon {
        display: block;
      }
    </style>
  </head>
  <!-- [Head] end -->
  
  <!-- [Body] Start -->
  <body>
    <!-- Theme initialization script -->
    <script>
      // Function to get theme from localStorage or default to light
      function getStoredTheme() {
        return localStorage.getItem('theme') || 'light';
      }
      
      // Function to set theme in localStorage
      function setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
      }
      
      // Function to update UI based on theme
      function updateThemeUI(theme) {
        // Update html attribute
        document.documentElement.setAttribute('data-pc-theme', theme);
        
        // Update the theme icon
        const themeIcon = document.getElementById('themeIcon');
        if (themeIcon) {
          if (theme === 'dark') {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
          } else {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
          }
        }
      }
      
      // Apply theme on page load
      document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = getStoredTheme();
        updateThemeUI(savedTheme);
      });
      
      // Toggle theme function
      function toggleTheme() {
        const currentTheme = getStoredTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        setStoredTheme(newTheme);
        updateThemeUI(newTheme);
      }
    </script>
    
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
      <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
        <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->