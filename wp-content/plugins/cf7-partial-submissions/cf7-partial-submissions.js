(function () {
  document.addEventListener('DOMContentLoaded', function () {
    // ---------------------
    // 1. Check or set session ID
    // ---------------------
    let sessionID = getCookie('cia_session_id');
    if (!sessionID) {
      console.warn('Session ID is missing! Generating a new one via JavaScript.');
      sessionID = generateRandomString(32);
      setCookie('cia_session_id', sessionID, 365); // 1 year
      console.log('Session ID set via JS:', sessionID);
    } else {
      console.log('Existing Session ID:', sessionID);
    }
    
    // ---------------------
    // 2. Handle all CF7 forms
    // ---------------------
    const cf7Forms = document.querySelectorAll('.wpcf7 form');
    cf7Forms.forEach(form => {
      // Insert or update hidden field with session ID
      let hiddenField = form.querySelector('input[name="cf7_session_id"]');
      if (!hiddenField) {
        hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'cf7_session_id';
        form.appendChild(hiddenField);
      }
      hiddenField.value = sessionID;
      
      // Debounce to avoid too-frequent calls
      const sendPartialData = debounce(function () {
        const formData = new FormData(form);
        const dataObj = {};
        formData.forEach((value, key) => {
          dataObj[key] = value;
        });
        
        // Prepare AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', cf7PartialSubmissions.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function () {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                // Optionally store the email in a cookie
                if (dataObj['your-email']) {
                  setCookie('cf7UserEmail', dataObj['your-email'], 30);
                } else if (dataObj['email']) {
                  setCookie('cf7UserEmail', dataObj['email'], 30);
                } else if (dataObj['contact-email']) {
                  setCookie('cf7UserEmail', dataObj['contact-email'], 30);
                }
              }
            } catch (e) {
              console.error('Error parsing server response:', e);
            }
          } else {
            console.error('Failed to send form data. Status:', xhr.status);
          }
        };
        
        const formIdValue = form.querySelector('input[name="_wpcf7"]')
          ? form.querySelector('input[name="_wpcf7"]').value
          : '';
        
        const postData = `action=cf7_partial_submissions_save_data&security=${encodeURIComponent(cf7PartialSubmissions.nonce)}`
          + `&session_id=${encodeURIComponent(sessionID)}`
          + `&form_id=${encodeURIComponent(formIdValue)}`
          + `&field_data=${encodeURIComponent(JSON.stringify(dataObj))}`;
        
        xhr.send(postData);
      }, 1000);
      
      // Trigger partial save
      form.addEventListener('blur', sendPartialData, true);
      form.addEventListener('input', sendPartialData);
    });
    
    
    // ---------------------
    // 3. Handle CUSTOM REGISTRATION FORM
    //    data-target="registration_form"
    // ---------------------
    const registrationForm = document.querySelector('[data-target="registration_form"]');
    if (registrationForm) {
      console.log('Found custom registration form. Enabling partial tracking...');
      
      // Insert or update hidden field with session ID
      let hiddenRegField = registrationForm.querySelector('input[name="session_id"]');
      if (!hiddenRegField) {
        hiddenRegField = document.createElement('input');
        hiddenRegField.type = 'hidden';
        hiddenRegField.name = 'session_id';
        registrationForm.appendChild(hiddenRegField);
      }
      hiddenRegField.value = sessionID;
      
      // We'll treat this custom form as form_id = 'registration_form'
      // so the plugin can differentiate it in the DB.
      const regFormId = 'registration_form';
      
      // Debounce call
      const sendPartialRegData = debounce(function () {
        const regFormData = new FormData(registrationForm);
        const regDataObj = {};
        regFormData.forEach((value, key) => {
          regDataObj[key] = value;
        });
        
        // Prepare AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', cf7PartialSubmissions.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function () {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                // Optionally store the email in a cookie
                if (regDataObj['mm_registration_email']) {
                  setCookie('cf7UserEmail', regDataObj['mm_registration_email'], 30);
                }
              }
            } catch (e) {
              console.error('Error parsing server response:', e);
            }
          } else {
            console.error('Failed to send form data. Status:', xhr.status);
          }
        };
        
        const postData = `action=cf7_partial_submissions_save_data&security=${encodeURIComponent(cf7PartialSubmissions.nonce)}`
          + `&session_id=${encodeURIComponent(sessionID)}`
          + `&form_id=${encodeURIComponent(regFormId)}`
          + `&field_data=${encodeURIComponent(JSON.stringify(regDataObj))}`;
        
        xhr.send(postData);
      }, 1000);
      
      // Trigger partial save on input/blur
      registrationForm.addEventListener('blur', sendPartialRegData, true);
      registrationForm.addEventListener('input', sendPartialRegData);
    }
    
    // ---------------------
    // 4. Handle LOGIN/REGISTRATION FORM
    // ---------------------
    const loginForm = document.querySelector('[data-target="login_register_form"]');
    if (loginForm) {
      console.log('Found login/registration form. Enabling partial tracking...');
      
      // Insert or update hidden field with session ID
      let hiddenLoginField = loginForm.querySelector('input[name="session_id"]');
      if (!hiddenLoginField) {
        hiddenLoginField = document.createElement('input');
        hiddenLoginField.type = 'hidden';
        hiddenLoginField.name = 'session_id';
        loginForm.appendChild(hiddenLoginField);
      }
      hiddenLoginField.value = sessionID;
      
      // Debounce function for partial data tracking
      const sendPartialLoginData = debounce(function () {
        const formData = new FormData(loginForm);
        const loginDataObj = {};
        formData.forEach((value, key) => {
          loginDataObj[key] = value;
        });
        
        // Prepare AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', cf7PartialSubmissions.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function () {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                console.log('Partial data saved for login/registration form.');
              }
            } catch (e) {
              console.error('Error parsing server response:', e);
            }
          } else {
            console.error('Failed to send partial data. Status:', xhr.status);
          }
        };
        
        const postData = `action=cf7_partial_submissions_save_data&security=${encodeURIComponent(cf7PartialSubmissions.nonce)}`
          + `&session_id=${encodeURIComponent(sessionID)}`
          + `&form_id=login_registration_form`
          + `&field_data=${encodeURIComponent(JSON.stringify(loginDataObj))}`;
        
        xhr.send(postData);
      }, 1000);
      
      // Track partial data on input and blur events
      loginForm.addEventListener('blur', sendPartialLoginData, true);
      loginForm.addEventListener('input', sendPartialLoginData);
      
      // Handle form submission to remove partial data
      loginForm.addEventListener('submit', function (event) {
        event.preventDefault();
        
        const formData = new FormData(loginForm);
        const loginDataObj = {};
        formData.forEach((value, key) => {
          loginDataObj[key] = value;
        });
        
        // Prepare AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', cf7PartialSubmissions.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function () {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                console.log('Form submitted successfully and entry removed.');
              }
            } catch (e) {
              console.error('Error parsing server response:', e);
            }
          } else {
            console.error('Failed to submit form data. Status:', xhr.status);
          }
        };
        
        const postData = `action=cf7_partial_submissions_save_data&security=${encodeURIComponent(cf7PartialSubmissions.nonce)}`
          + `&session_id=${encodeURIComponent(sessionID)}`
          + `&form_id=login_registration_form`
          + `&field_data=${encodeURIComponent(JSON.stringify(loginDataObj))}`;
        
        xhr.send(postData);
      });
    }
    
    // ---------------------
    // Helper: Debounce
    // ---------------------
    function debounce(func, delay) {
      let timer;
      return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
      };
    }
    
    // ---------------------
    // Helper: Generate random string
    // ---------------------
    function generateRandomString(length = 16) {
      const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      let result = '';
      for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      return result;
    }
    
    // ---------------------
    // Helper: Set cookie
    // ---------------------
    function setCookie(name, value, days) {
      const date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      const expires = 'expires=' + date.toUTCString();
      document.cookie = name + '=' + value + ';' + expires + ';path=/';
    }
    
    // ---------------------
    // Helper: Get cookie
    // ---------------------
    function getCookie(name) {
      const nameEQ = name + '=';
      const cookies = document.cookie.split(';');
      for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(nameEQ) === 0) {
          return cookie.substring(nameEQ.length, cookie.length);
        }
      }
      return null;
    }
  });
})();
