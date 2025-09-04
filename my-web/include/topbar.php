<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="pc-header">
  <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">
    <!-- [Mobile Media Block] start -->
    <div class="me-auto pc-mob-drp">
      <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
        <!-- ======== Menu collapse Icon ===== -->
        <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
          <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide">
            <i data-feather="menu"></i>
          </a>
        </li>
        <li class="pc-h-item pc-sidebar-popup lg:hidden">
          <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse">
            <i data-feather="menu"></i>
          </a>
        </li>
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" aria-expanded="false">
            <i data-feather="search"></i>
          </a>
          <div class="dropdown-menu pc-h-dropdown drp-search">
            <form class="px-2 py-1">
              <input type="search" class="form-control !border-0 !shadow-none" placeholder="Search here. . ." />
            </form>
          </div>
        </li>
      </ul>
    </div>
    <!-- [Mobile Media Block end] -->
    <div class="ms-auto">
      <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" aria-expanded="false">
            <i data-feather="sun"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
            <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
              <i data-feather="moon"></i>
              <span>Dark</span>
            </a>
            <a href="#!" class="dropdown-item" onclick="layout_change('light')">
              <i data-feather="sun"></i>
              <span>Light</span>
            </a>
            <a href="#!" class="dropdown-item" onclick="layout_change_default()">
              <i data-feather="settings"></i>
              <span>Default</span>
            </a>
          </div>
        </li>
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" aria-expanded="false">
            <i data-feather="settings"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
            <a href="#!" class="dropdown-item">
              <i class="ti ti-user"></i>
              <span>My Account</span>
            </a>
            <a href="#!" class="dropdown-item">
              <i class="ti ti-settings"></i>
              <span>Settings</span>
            </a>
            <a href="#!" class="dropdown-item">
              <i class="ti ti-headset"></i>
              <span>Support</span>
            </a>
            <a href="#!" class="dropdown-item">
              <i class="ti ti-lock"></i>
              <span>Lock Screen</span>
            </a>
            <a href="logout.php" class="dropdown-item">
              <i class="ti ti-power"></i>
              <span>Logout</span>
            </a>
          </div>
        </li>
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" aria-expanded="false">
            <i data-feather="bell"></i>
            <span class="badge bg-success-500 text-white rounded-full z-10 absolute right-0 top-0">3</span>
          </a>
          <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown p-2">
            <div class="dropdown-header flex items-center justify-between py-4 px-5">
              <h5 class="m-0">Notifications</h5>
              <a href="#!" class="btn btn-link btn-sm">Mark all read</a>
            </div>
            <div class="dropdown-body header-notification-scroll relative py-4 px-5"
              style="max-height: calc(100vh - 215px)">
              <p class="text-span mb-3">Today</p>
              
            </div>
            <div class="text-center py-2">
              <a href="#!" class="text-danger-500 hover:text-danger-600 focus:text-danger-600 active:text-danger-600">
                Clear all Notifications
              </a>
            </div>
          </div>
        </li>
        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">
            <i data-feather="user"></i>
          </a>
          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">
            <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
              <div class="flex mb-1 items-center">
                <div class="shrink-0">
                  <img src="../assets/images/profile.jpg" alt="user-image" class="w-10 rounded-full" />
                </div>
                <div class="grow ms-3">
                  <h6 class="mb-1 text-white"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?> 🖖</h6>
                  <span class="text-white"><?php echo htmlspecialchars($_SESSION['email'] ?? 'user@example.com'); ?></span>
                </div>
              </div>
            </div>
            <div class="dropdown-body py-4 px-5">
              <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 225px)">
                <a href="#" class="dropdown-item">
                  <span>
                    <svg class="pc-icon text-muted me-2 inline-block">
                      <use xlink:href="#custom-setting-outline"></use>
                    </svg>
                    <span>Settings</span>
                  </span>
                </a>
                <a href="#" class="dropdown-item">
                  <span>
                    <svg class="pc-icon text-muted me-2 inline-block">
                      <use xlink:href="#custom-share-bold"></use>
                    </svg>
                    <span>Share</span>
                  </span>
                </a>
                <a href="#" class="dropdown-item">
                  <span>
                    <svg class="pc-icon text-muted me-2 inline-block">
                      <use xlink:href="#custom-lock-outline"></use>
                    </svg>
                    <span>Change Password</span>
                  </span>
                </a>
                <div class="grid my-3">
                  <a href="logout.php" class="btn btn-primary flex items-center justify-center">
                    <svg class="pc-icon me-2 w-[22px] h-[22px]">
                      <use xlink:href="#custom-logout-1-outline"></use>
                    </svg>
                    Logout
                  </a>
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</header>