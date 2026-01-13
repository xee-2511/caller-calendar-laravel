<!doctype html>

<html
  lang="en"
  class="layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="ltr"
  data-skin="default"
  data-assets-path="{{config('app.url')}}assets/"
  data-template="vertical-menu-template"
  data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>{{config('app.name')}}</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{config('app.url')}}assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/node-waves/node-waves.css" />

    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/pickr/pickr-themes.css" />

    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/css/core.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/css/demo.css" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/@form-validation/form-validation.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/css/pages/page-icons.css" />
    <link rel="stylesheet" href="{{config('app.url')}}assets/vendor/libs/flatpickr/flatpickr.css" />

    <style>
      .dropdown-user .dropdown-menu{
        
      }
    </style>
    <!-- Page CSS -->
    @stack('stylesheets')

    <!-- Helpers -->
    <script src="{{config('app.url')}}assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <!--<script src="{{config('app.url')}}assets/vendor/js/template-customizer.js"></script>-->

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{config('app.url')}}assets/js/config.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        @include('caller.layouts.menu')

        <div class="menu-mobile-toggler d-xl-none rounded-1">
          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
            <i class="ti tabler-menu icon-base"></i>
            <i class="ti tabler-chevron-right icon-base"></i>
          </a>
        </div>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          
            @include('caller.layouts.header')

          <!-- Content wrapper -->
          @yield('main-content')
          <!-- Content wrapper -->

          @include('caller.layouts.footer')

          <div class="content-backdrop fade"></div>
          </div>
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    @stack('modals')

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/theme.js -->

    <script src="{{config('app.url')}}assets/vendor/libs/jquery/jquery.js"></script>

    <script src="{{config('app.url')}}assets/vendor/libs/popper/popper.js"></script>
    <script src="{{config('app.url')}}assets/vendor/js/bootstrap.js"></script>
    <script src="{{config('app.url')}}assets/vendor/libs/node-waves/node-waves.js"></script>

    <script src="{{config('app.url')}}assets/vendor/libs/@algolia/autocomplete-js.js"></script>

    <script src="{{config('app.url')}}assets/vendor/libs/pickr/pickr.js"></script>

    <script src="{{config('app.url')}}assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="{{config('app.url')}}assets/vendor/libs/hammer/hammer.js"></script>

    <script src="{{config('app.url')}}assets/vendor/libs/i18n/i18n.js"></script>

    <script src="{{config('app.url')}}assets/vendor/js/menu.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{config('app.url')}}assets/vendor/libs/moment/moment.js"></script>
    <script src="{{config('app.url')}}assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="{{config('app.url')}}assets/vendor/libs/select2/select2.js"></script>
    <script src="{{config('app.url')}}assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="{{config('app.url')}}assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="{{config('app.url')}}assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="{{config('app.url')}}assets/vendor/libs/cleave-zen/cleave-zen.js"></script>

    <!-- Main JS -->

    <script src="{{config('app.url')}}assets/js/main.js"></script>

    <!-- Page JS -->
    @stack('scripts')
  </body>
</html>
