/**
 * AdminLTE Demo Menu
 * ------------------
 * You should not use this file in production.
 * This file is for demo purposes only.
 */
(function ($) {
  'use strict'

  var $sidebar = $('.control-sidebar')
  var $container = $('<div />', {
    class: 'p-3 control-sidebar-content'
  })

  $sidebar.append($container)

  var $customize_menu = $('<div />')

  // AJAX Save Function
  function saveThemePreference(key, value) {
    var payload = {
      key: key,
      value: value,
      _token: $('meta[name="csrf-token"]').attr('content')
    };

    $.ajax({
      url: '/user/theme',
      method: 'POST',
      data: payload,
      success: function (response) {
        console.log('Theme saved:', key, value);
      },
      error: function (xhr) {
        console.error('Error saving theme:', xhr);
      }
    });
  }

  function createCheckboxKey(label, checked, key, onChange) {
    var $container = $('<div />', {
      class: 'mb-1'
    })

    var $customControl = $('<div />', {
      class: 'custom-control custom-checkbox mb-1'
    })

    var id = 'customCheckbox' + Math.floor(Math.random() * 100000)

    var $checkbox = $('<input />', {
      class: 'custom-control-input',
      type: 'checkbox',
      id: id,
      checked: checked
    }).on('click', function () {
      var isChecked = $(this).is(':checked');
      onChange(isChecked);
      saveThemePreference(key, isChecked);
    })

    var $label = $('<label />', {
      class: 'custom-control-label',
      for: id,
      text: label
    })

    $customControl.append($checkbox).append($label)
    $container.append($customControl)

    return $container
  }

  function createCheckbox(label, checked, onChange) {
    // Legacy wrapper if needed, but we should upgrade all calls.
    // But for "Dark Mode" we added key.
    return createCheckboxKey(label, checked, 'unknown', onChange);
  }

  // Dark Mode
  $customize_menu.append(createCheckboxKey('Dark Mode', $('body').hasClass('dark-mode'), 'dark_mode', function (checked) {
    if (checked) {
      $('body').addClass('dark-mode')
    } else {
      $('body').removeClass('dark-mode')
    }
  }))

  $customize_menu.append('<h5>Header Options</h5>')

  // Header Fixed
  $customize_menu.append(createCheckboxKey('Fixed', $('body').hasClass('layout-navbar-fixed'), 'layout_navbar_fixed', function (checked) {
    if (checked) {
      $('body').addClass('layout-navbar-fixed')
    } else {
      $('body').removeClass('layout-navbar-fixed')
    }
  }))

  // No Border
  $customize_menu.append(createCheckboxKey('No Border', $('.main-header').hasClass('border-bottom-0'), 'navbar_no_border', function (checked) {
    if (checked) {
      $('.main-header').addClass('border-bottom-0')
    } else {
      $('.main-header').removeClass('border-bottom-0')
    }
  }))

  $customize_menu.append('<h6>Sidebar Options</h6>')

  // Sidebar Collapsed
  $customize_menu.append(createCheckboxKey('Collapsed', $('body').hasClass('sidebar-collapse'), 'sidebar_collapse', function (checked) {
    if (checked) {
      $('body').addClass('sidebar-collapse')
      $(window).trigger('resize')
    } else {
      $('body').removeClass('sidebar-collapse')
      $(window).trigger('resize')
    }
  }))

  // Sidebar Fixed
  $customize_menu.append(createCheckboxKey('Fixed', $('body').hasClass('layout-fixed'), 'layout_fixed', function (checked) {
    if (checked) {
      $('body').addClass('layout-fixed')
      $(window).trigger('resize')
    } else {
      $('body').removeClass('layout-fixed')
      $(window).trigger('resize')
    }
  }))

  // Sidebar Mini
  $customize_menu.append(createCheckboxKey('Sidebar Mini', $('body').hasClass('sidebar-mini'), 'sidebar_mini', function (checked) {
    if (checked) {
      $('body').addClass('sidebar-mini')
    } else {
      $('body').removeClass('sidebar-mini')
    }
  }))

  // Sidebar Mini MD
  $customize_menu.append(createCheckboxKey('Sidebar Mini MD', $('body').hasClass('sidebar-mini-md'), 'sidebar_mini_md', function (checked) {
    if (checked) {
      $('body').addClass('sidebar-mini-md')
    } else {
      $('body').removeClass('sidebar-mini-md')
    }
  }))

  // Sidebar Mini XS
  $customize_menu.append(createCheckboxKey('Sidebar Mini XS', $('body').hasClass('sidebar-mini-xs'), 'sidebar_mini_xs', function (checked) {
    if (checked) {
      $('body').addClass('sidebar-mini-xs')
    } else {
      $('body').removeClass('sidebar-mini-xs')
    }
  }))

  // Nav Flat Style
  $customize_menu.append(createCheckboxKey('Nav Flat Style', $('.nav-sidebar').hasClass('nav-flat'), 'sidebar_nav_flat', function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-flat')
    } else {
      $('.nav-sidebar').removeClass('nav-flat')
    }
  }))

  // Nav Legacy Style
  $customize_menu.append(createCheckboxKey('Nav Legacy Style', $('.nav-sidebar').hasClass('nav-legacy'), 'sidebar_nav_legacy', function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-legacy')
    } else {
      $('.nav-sidebar').removeClass('nav-legacy')
    }
  }))

  // Nav Compact
  $customize_menu.append(createCheckboxKey('Nav Compact', $('.nav-sidebar').hasClass('nav-compact'), 'sidebar_nav_compact', function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-compact')
    } else {
      $('.nav-sidebar').removeClass('nav-compact')
    }
  }))

  // Nav Child Indent
  $customize_menu.append(createCheckboxKey('Nav Child Indent', $('.nav-sidebar').hasClass('nav-child-indent'), 'sidebar_nav_child_indent', function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-child-indent')
    } else {
      $('.nav-sidebar').removeClass('nav-child-indent')
    }
  }))

  // Nav Child Hide on Collapse
  $customize_menu.append(createCheckboxKey('Nav Child Hide on Collapse', $('.nav-sidebar').hasClass('nav-collapse-hide-child'), 'sidebar_nav_child_hide', function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-collapse-hide-child')
    } else {
      $('.nav-sidebar').removeClass('nav-collapse-hide-child')
    }
  }))

  // Disable Hover/Focus Auto-Expand
  $customize_menu.append(createCheckboxKey('Disable Hover/Focus Auto-Expand', $('.main-sidebar').hasClass('sidebar-no-expand'), 'sidebar_no_expand', function (checked) {
    if (checked) {
      $('.main-sidebar').addClass('sidebar-no-expand')
    } else {
      $('.main-sidebar').removeClass('sidebar-no-expand')
    }
  }))

  $customize_menu.append('<h6>Footer Options</h6>')

  // Footer Fixed
  $customize_menu.append(createCheckboxKey('Fixed', $('body').hasClass('layout-footer-fixed'), 'layout_footer_fixed', function (checked) {
    if (checked) {
      $('body').addClass('layout-footer-fixed')
    } else {
      $('body').removeClass('layout-footer-fixed')
    }
  }))

  // Small Text Options
  $customize_menu.append('<h6>Small Text Options</h6>')

  var $text_sm_body_checkbox = createCheckboxKey('Body', $('body').hasClass('text-sm'), 'body_text_sm', function (checked) {
    if (checked) {
      $('body').addClass('text-sm')
    } else {
      $('body').removeClass('text-sm')
    }
  })
  $customize_menu.append($text_sm_body_checkbox)

  $customize_menu.append(createCheckboxKey('Navbar', $('.main-header').hasClass('text-sm'), 'navbar_text_sm', function (checked) {
    if (checked) {
      $('.main-header').addClass('text-sm')
    } else {
      $('.main-header').removeClass('text-sm')
    }
  }))

  $customize_menu.append(createCheckboxKey('Brand', $('.brand-link').hasClass('text-sm'), 'brand_text_sm', function (checked) {
    if (checked) {
      $('.brand-link').addClass('text-sm')
    } else {
      $('.brand-link').removeClass('text-sm')
    }
  }))

  $customize_menu.append(createCheckboxKey('Sidebar Nav', $('.nav-sidebar').hasClass('text-sm'), 'sidebar_nav_text_sm', function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('text-sm')
    } else {
      $('.nav-sidebar').removeClass('text-sm')
    }
  }))

  $customize_menu.append(createCheckboxKey('Footer', $('.main-footer').hasClass('text-sm'), 'footer_text_sm', function (checked) {
    if (checked) {
      $('.main-footer').addClass('text-sm')
    } else {
      $('.main-footer').removeClass('text-sm')
    }
  }))

  // Color Variants
  function createSkinBlock(colors, callback, noneSelected) {
    var $block = $('<div />', {
      class: 'd-flex flex-wrap mb-3'
    })

    colors.forEach(function (color) {
      var $color = $('<div />', {
        class: 'elevation-2 my-1 ' + (noneSelected ? 'bg-gray' : 'bg-' + color),
        style: 'width: 25px; height: 10px; border-radius: 25px; cursor: pointer; margin-right: 5px;',
      })

      $color.on('click', function () {
        callback(color)
      })

      // Add tooltip/title
      $color.attr('title', color)

      $block.append($color)
    })

    return $block
  }

  // Navbar Variants
  $customize_menu.append('<h6>Navbar Variants</h6>')

  var navbar_colors = [
    'primary', 'secondary', 'info', 'success', 'danger', 'indigo', 'purple', 'pink', 'navy', 'lightblue', 'teal', 'cyan', 'dark', 'gray-dark', 'gray'
  ]

  var $navbar_variants = $('<div />', {
    class: 'd-flex flex-wrap mb-3'
  })

  // Light variants
  var navbar_light_colors = [
    'light', 'warning', 'white', 'orange'
  ]

  $customize_menu.append(createSkinBlock(navbar_colors, function (color) {
    var $main_header = $('.main-header')
    $main_header.removeClass('navbar-dark').addClass('navbar-dark')
    navbar_colors.concat(navbar_light_colors).forEach(function (c) {
      $main_header.removeClass('bg-' + c)
      $main_header.removeClass('navbar-' + c)
    })
    $main_header.addClass('bg-' + color)
    $main_header.addClass('navbar-' + color)

    saveThemePreference('navbar_variant', 'navbar-dark navbar-' + color + ' bg-' + color);
  }, false))

  $customize_menu.append(createSkinBlock(navbar_light_colors, function (color) {
    var $main_header = $('.main-header')
    $main_header.removeClass('navbar-dark').addClass('navbar-light')
    navbar_colors.concat(navbar_light_colors).forEach(function (c) {
      $main_header.removeClass('bg-' + c)
      $main_header.removeClass('navbar-' + c)
    })
    $main_header.addClass('bg-' + color)
    $main_header.addClass('navbar-' + color)

    saveThemePreference('navbar_variant', 'navbar-light navbar-' + color + ' bg-' + color);
  }, false))


  // Sidebar Variants
  $customize_menu.append('<h6>Dark Sidebar Variants</h6>')
  var sidebar_colors = [
    'primary', 'warning', 'info', 'danger', 'success', 'indigo', 'lightblue', 'navy', 'purple', 'fuchsia', 'pink', 'maroon', 'orange', 'lime', 'teal', 'olive'
  ]

  $customize_menu.append(createSkinBlock(sidebar_colors, function (color) {
    var $sidebar = $('.main-sidebar')
    var sidebar_class = 'sidebar-dark-' + color
    var $all_sidebar_classes = sidebar_colors.map(function (c) {
      return 'sidebar-dark-' + c
    })

    $sidebar.removeClass($all_sidebar_classes.join(' '))
    $sidebar.addClass(sidebar_class)

    saveThemePreference('sidebar_variant', sidebar_class);
  }, false))

  $customize_menu.append('<h6>Light Sidebar Variants</h6>')
  $customize_menu.append(createSkinBlock(sidebar_colors, function (color) {
    var $sidebar = $('.main-sidebar')
    var sidebar_class = 'sidebar-light-' + color
    var $all_sidebar_classes = sidebar_colors.map(function (c) {
      return 'sidebar-light-' + c
    })

    $sidebar.removeClass($all_sidebar_classes.join(' '))
    $sidebar.removeClass('sidebar-dark-primary') // remove default
    $sidebar.addClass(sidebar_class)

    saveThemePreference('sidebar_variant', sidebar_class);
  }, false))

  $container.append($customize_menu)
})(jQuery)
