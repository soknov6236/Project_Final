    <!-- [ Main Content ] end -->
    <footer class="pc-footer">
      <div class="footer-wrapper container-fluid mx-10">
        <div class="grid grid-cols-12 gap-1.5">
          <div class="col-span-12 sm:col-span-6 my-1">
            <p class="m-0"></p>
              <a href="#" class="text-theme-bodycolor dark:text-themedark-bodycolor hover:text-primary-500 dark:hover:text-primary-500" target="_blank">Welcome</a>
              , Nisai ♥ Thank for Support.
            </p>
          </div>
          <div class="col-span-12 sm:col-span-3 my-2 justify-self-end">
                   <p class="inline-block max-sm:mr-3 sm:ml-2">Distributed by <a href="#" target="_blank">Nov, Lem</a></p>
          </div>
        </div>
      </div>
    </footer>
 
    <!-- Required Js -->
    <script src="../assets/js/plugins/simplebar.min.js"></script>
    <script src="../assets/js/plugins/popper.min.js"></script>
    <script src="../assets/js/icon/custom-icon.js"></script>
    <script src="../assets/js/plugins/feather.min.js"></script>
    <script src="../assets/js/component.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/script.js"></script>

    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
    </div>

 <!-- Updated JavaScript functions to use localStorage -->
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
