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

  function createCheckbox(label, checked, onChange) {
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
      onChange($(this).is(':checked'))
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

  // Dark Mode
  $customize_menu.append(createCheckbox('Dark Mode', $('body').hasClass('dark-mode'), function (checked) {
    if (checked) {
      $('body').addClass('dark-mode')
    } else {
      $('body').removeClass('dark-mode')
    }
  }))

  $customize_menu.append('<h5>Header Options</h5>')

  // Header Fixed
  $customize_menu.append(createCheckbox('Fixed', $('body').hasClass('layout-navbar-fixed'), function (checked) {
    if (checked) {
      $('body').addClass('layout-navbar-fixed')
    } else {
      $('body').removeClass('layout-navbar-fixed')
    }
  }))

  // No Border
  $customize_menu.append(createCheckbox('No Border', $('body').hasClass('border-bottom-0'), function (checked) {
    if (checked) {
      $('.main-header').addClass('border-bottom-0')
    } else {
      $('.main-header').removeClass('border-bottom-0')
    }
  }))

  $customize_menu.append('<h6>Sidebar Options</h6>')

  // Sidebar Collapsed
  $customize_menu.append(createCheckbox('Collapsed', $('body').hasClass('sidebar-collapse'), function (checked) {
    if (checked) {
      $('body').addClass('sidebar-collapse')
      $(window).trigger('resize')
    } else {
      $('body').removeClass('sidebar-collapse')
      $(window).trigger('resize')
    }
  }))

  // Sidebar Fixed
  $customize_menu.append(createCheckbox('Fixed', $('body').hasClass('layout-fixed'), function (checked) {
    if (checked) {
      $('body').addClass('layout-fixed')
      $(window).trigger('resize')
    } else {
      $('body').removeClass('layout-fixed')
      $(window).trigger('resize')
    }
  }))

  // Sidebar Mini
  $customize_menu.append(createCheckbox('Sidebar Mini', $('body').hasClass('sidebar-mini'), function (checked) {
    if (checked) {
      $('body').addClass('sidebar-mini')
    } else {
      $('body').removeClass('sidebar-mini')
    }
  }))

  // Sidebar Mini MD
  $customize_menu.append(createCheckbox('Sidebar Mini MD', $('body').hasClass('sidebar-mini-md'), function (checked) {
    if (checked) {
      $('body').addClass('sidebar-mini-md')
    } else {
      $('body').removeClass('sidebar-mini-md')
    }
  }))

  // Sidebar Mini XS
  $customize_menu.append(createCheckbox('Sidebar Mini XS', $('body').hasClass('sidebar-mini-xs'), function (checked) {
    if (checked) {
      $('body').addClass('sidebar-mini-xs')
    } else {
      $('body').removeClass('sidebar-mini-xs')
    }
  }))

  // Nav Flat Style
  $customize_menu.append(createCheckbox('Nav Flat Style', $('.nav-sidebar').hasClass('nav-flat'), function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-flat')
    } else {
      $('.nav-sidebar').removeClass('nav-flat')
    }
  }))

  // Nav Legacy Style
  $customize_menu.append(createCheckbox('Nav Legacy Style', $('.nav-sidebar').hasClass('nav-legacy'), function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-legacy')
    } else {
      $('.nav-sidebar').removeClass('nav-legacy')
    }
  }))

  // Nav Compact
  $customize_menu.append(createCheckbox('Nav Compact', $('.nav-sidebar').hasClass('nav-compact'), function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-compact')
    } else {
      $('.nav-sidebar').removeClass('nav-compact')
    }
  }))

  // Nav Child Indent
  $customize_menu.append(createCheckbox('Nav Child Indent', $('.nav-sidebar').hasClass('nav-child-indent'), function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-child-indent')
    } else {
      $('.nav-sidebar').removeClass('nav-child-indent')
    }
  }))

  // Nav Child Hide on Collapse
  $customize_menu.append(createCheckbox('Nav Child Hide on Collapse', $('.nav-sidebar').hasClass('nav-collapse-hide-child'), function (checked) {
    if (checked) {
      $('.nav-sidebar').addClass('nav-collapse-hide-child')
    } else {
      $('.nav-sidebar').removeClass('nav-collapse-hide-child')
    }
  }))

  // Disable Hover/Focus Auto-Expand
  $customize_menu.append(createCheckbox('Disable Hover/Focus Auto-Expand', $('.main-sidebar').hasClass('sidebar-no-expand'), function (checked) {
    if (checked) {
      $('.main-sidebar').addClass('sidebar-no-expand')
    } else {
      $('.main-sidebar').removeClass('sidebar-no-expand')
    }
  }))

  $customize_menu.append('<h6>Footer Options</h6>')

  // Footer Fixed
  $customize_menu.append(createCheckbox('Fixed', $('body').hasClass('layout-footer-fixed'), function (checked) {
    if (checked) {
      $('body').addClass('layout-footer-fixed')
    } else {
      $('body').removeClass('layout-footer-fixed')
    }
  }))

  $container.append($customize_menu)

})(jQuery)
