/**
 *
 * You can write your JS code here, DO NOT touch the default style file
 * because it will make it harder for you to update.
 *
 */

"use strict";

$("#identity").keydown(function (e) {
  if (e.which === 38 || e.which === 40) {
    e.preventDefault();
  }
});
$("#number").keydown(function (e) {
  if (e.which === 38 || e.which === 40) {
    e.preventDefault();
  }
});
$("#otp").keydown(function (e) {
  if (e.which === 38 || e.which === 40) {
    e.preventDefault();
  }
});

setTimeout(function () {
  $("#logout_msg").hide("slow");
}, 2000);
$(".otp_show").hide();
$("#step_2").hide();
$("#steper_1").addClass("bg-primary");
$("#steper_2").addClass("bg-dark");

function render() {
  window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier("rec");
  recaptchaVerifier.render();
}
// function for send message
var cd = {};
// const code_result = [];
function phoneAuth(code_result) {
  var number =
    "" +
    document.getElementById("country_code").value +
    document.getElementById("number").value;
  document.getElementById("phone").value =
    document.getElementById("number").value;
  document.getElementById("store_country_code").value =
    document.getElementById("country_code").value;

  firebase
    .auth()
    .signInWithPhoneNumber(number, window.recaptchaVerifier)
    .then(function (confirmationResult) {
      window.confirmationResult = confirmationResult;
      // console.log(confirmationResult);
      code_result = confirmationResult;
      cd = code_result;
      $("#send").hide();
      $(".otp_show").show();
      $(".step").html(2);
    })
    .catch(function (error) {
      alert(error.message);
    });
}

// function for code verify

function sms_codeverify() {
  if ($("#otp").val() == "") {
    Swal.fire({
      icon: "warning",
      title: oops + "...",
      text: please_enter_otp_before_proceeding_any_further,
    });
  } else {
    var code = document.getElementById("otp").value;
    $.ajax({
      type: "POST",
      url: "verify_sms_otp",
      data: {
        code: code,
        number: document.getElementById("number").value,
      },
      dataType: "json",
      success: function (response) {
        if (response.error == false) {
          showToastMessage(response.message, "success");
          $("#step_2").show();
          $("#step_1").hide();
          $(".step").html(3);
          $("#steper_1").addClass("bg-dark");
          $("#steper_2").removeClass("bg-dark");
          $("#steper_2").addClass("bg-primary");
        } else {
          Swal.fire({
            icon: "error",
            title: oops + "...",
            text: entered_otp_is_wrong_please_confirm_it_and_try_again,
          });
        }
      },
    });
  }
}

function codeverify() {
  if ($("#otp").val() == "") {
    Swal.fire({
      icon: "warning",
      title: oops + "...",
      text: please_enter_otp_before_proceeding_any_further,
    });
  } else {
    var code = document.getElementById("otp").value;
    cd.confirm(code)
      .then(function () {
        $("#step_2").show();
        $("#step_1").hide();
        $(".step").html(3);

        $("#steper_1").addClass("bg-dark");
        $("#steper_2").removeClass("bg-dark");
        $("#steper_2").addClass("bg-primary");
      })
      .catch(function () {
        Swal.fire({
          icon: "error",
          title: oops + "...",
          text: entered_otp_is_wrong_please_confirm_it_and_try_again,
        });
      });
  }
}
$(document).ready(() => {
  //select2 - only initialize if there are multiple country codes
  setTimeout(() => {
    // Check if country_code element is a select (dropdown) and not a read-only input
    var countryCodeElement = $("#country_code");
    if (countryCodeElement.length > 0 && countryCodeElement.prop('tagName') === 'SELECT') {
      countryCodeElement.select2({
        placeholder: select_country_code,
      });
    }
  }, 100);
});

function sms_phoneAuth() {
  $.ajax({
    type: "POST",
    url: "send_sms_otp",
    data: {
      number: document.getElementById("number").value,
      country_code: document.getElementById("country_code").value,
    },
    dataType: "json",
    success: function (response) {
      if (response.error == false) {
        showToastMessage(response.message, "success");
        $("#send").hide();
        $(".otp_show").show();
        $(".step").html(2);
      } else {
        showToastMessage(response.message, "error");
      }
    },
  });
}

$("#sender").on("click", function () {
  $.ajax({
    type: "POST",
    url: "check_number",
    data: {
      number: document.getElementById("number").value,
      country_code: document.getElementById("country_code").value,
    },

    dataType: "json",
    success: function (response) {
      if (response.error == false) {
        if (response.data.authentication_mode == "sms_gateway") {
          sms_phoneAuth();
        } else if (response.data.authentication_mode == "firebase") {
          phoneAuth();
        }
      } else {
        showToastMessage(response.message, "error");

        setTimeout(function() {
          window.location.href = baseUrl + "/auth/create_user";
        }, 60000);
      
      }
    },
  });
});

$("#sender_forgot_password").on("click", function () {
  $.ajax({
    type: "POST",
    url: "check_number_for_forgot_password",
    data: {
      number: document.getElementById("number").value,
      country_code: document.getElementById("country_code").value,
    },

    dataType: "json",
    success: function (response) {
      csrfName = response.csrfName;
      csrfHash = response.csrfHash;

      if (response.error == false) {
        if (response.data.authentication_mode == "sms_gateway") {
          SMSphoneAuthForForgotPassword();
        } else if (response.data.authentication_mode == "firebase") {
          phoneAuthForForgotPassword();
        }
      } else {
    
        showToastMessage(response.message, "error");
        setTimeout(function() {
          window.location.href = baseUrl + "/auth/forgot-password/";
        }, 60000);
        
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      // Handle the error here
      console.error("Ajax request failed:", jqXHR.status, textStatus);
      console.error("Error Details:", errorThrown);

      // Check if the response is HTML or some other non-JSON content
      if (jqXHR.status === 200 && jqXHR.responseText.startsWith("<")) {
        // The response is HTML; handle it as needed
        console.error("HTML response received:", jqXHR.responseText);
        // Display an error message or take appropriate action
      } else {
        // The response is not HTML; it might be another format or an unexpected error
        // Handle it as needed
      }
    },
  });
});

function SMSphoneAuthForForgotPassword() {
  $.ajax({
    type: "POST",
    url: "send_sms_otp",
    data: {
      number: document.getElementById("number").value,
      country_code: document.getElementById("country_code").value,
    },
    dataType: "json",
    success: function (response) {
      if (response.error == false) {
        showToastMessage(response.message, "success");
        $("#send").hide();
        $(".otp_show").show();
        setTimeout(function () {
          $(".step").html(2);
        }, 60000);
        // $(".step").html(2);
      } else {
        showToastMessage(response.message, "error");
      }
    },
  });
}
function phoneAuthForForgotPassword(code_result) {
  var number =
    "" +
    document.getElementById("country_code").value +
    document.getElementById("number").value;
  document.getElementById("phone").value =
    document.getElementById("number").value;
  document.getElementById("store_country_code").value =
    document.getElementById("country_code").value;

  firebase
    .auth()
    .signInWithPhoneNumber(number, window.recaptchaVerifier)
    .then(function (confirmationResult) {
      window.confirmationResult = confirmationResult;
      // console.log(confirmationResult);
      code_result = confirmationResult;
      cd = code_result;

      $("#send").hide();
      $(".otp_show").show();
      // $(".step").html(2);

      setTimeout(function () {
        $(".step").html(2);
      }, 60000);
    })
    .catch(function (error) {
      alert(error.message);
    });
}
$("#register").on("submit", function (e) {
  e.preventDefault();

  var form = $(this);
  $.ajax({
    type: "POST",
    url: baseUrl + "/auth/reset",
    data: form.serialize(),
    dataType: "json",

    success: function (response) {
      // console.log(response);
      // console.log("success");
      if (response.error == false) {
        window.location.href = baseUrl + "/partner/login";
      } else {
        iziToast.error({
          title: "",
          message: response.message,
          position: "topRight",
        });
      }
    },
  });
});

$("#forgot_password").on("submit", function (e) {
  e.preventDefault();

  // Get password values
  var password = $("#password").val();
  var password_confirm = $("#password_confirm").val();

  // Client-side validation: Check if passwords are empty
  if (!password || password.trim() === "") {
    iziToast.error({
      title: "",
      message: "Password is required",
      position: "topRight",
    });
    $("#password").focus();
    return false;
  }

  if (!password_confirm || password_confirm.trim() === "") {
    iziToast.error({
      title: "",
      message: "Confirm password is required",
      position: "topRight",
    });
    $("#password_confirm").focus();
    return false;
  }

  // Client-side validation: Check if passwords match
  if (password !== password_confirm) {
    iziToast.error({
      title: "",
      message: typeof password_mismatch !== 'undefined' ? password_mismatch : "Passwords do not match",
      position: "topRight",
    });
    $("#password").css("border-color", "#FF3300");
    $("#password_confirm").css("border-color", "#FF3300");
    $("#password_confirm").focus();
    return false;
  }

  // Reset border colors if validation passes
  $("#password").css("border-color", "");
  $("#password_confirm").css("border-color", "");

  var form = $(this);
  $.ajax({
    type: "POST",
    url: baseUrl + "/auth/reset_password_otp",
    data: form.serialize(),
    dataType: "json",

    success: function (response) {
      // Handle response message (could be string, array, or object)
      var message = response.message;
      if (typeof message === 'object') {
        // If message is an object/array, convert to string
        if (Array.isArray(message)) {
          message = message.join(', ');
        } else {
          message = JSON.stringify(message);
        }
      }

      if (response.error == false) {
        // Show success toast message
        iziToast.success({
          title: "",
          message: message || "Password reset successfully",
          position: "topRight",
        });
        // Redirect after a short delay to allow user to see the success message
        // Add query parameter to show success message on login page
        // Determine login URL based on userType
        var userType = $("#userType").val();
        var loginUrl = baseUrl + "/partner/login?password_changed=1";
        if (userType === "admin") {
          loginUrl = baseUrl + "/admin/login?password_changed=1";
        }
        setTimeout(function() {
          window.location.href = loginUrl;
        }, 1500);
      } else {
        // Show error toast message
        iziToast.error({
          title: "",
          message: message || "Something went wrong",
          position: "topRight",
        });
      }
    },
    error: function (xhr, status, error) {
      // Handle AJAX errors
      var errorMessage = "Something went wrong";
      if (xhr.responseJSON && xhr.responseJSON.message) {
        var msg = xhr.responseJSON.message;
        if (typeof msg === 'object') {
          if (Array.isArray(msg)) {
            errorMessage = msg.join(', ');
          } else {
            errorMessage = JSON.stringify(msg);
          }
        } else {
          errorMessage = msg;
        }
      }
      iziToast.error({
        title: "",
        message: errorMessage,
        position: "topRight",
      });
    },
  });
});
