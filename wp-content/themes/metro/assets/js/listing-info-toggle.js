document.addEventListener("DOMContentLoaded", function () {
  const infoIcon = document.querySelector("#listing-typediv .info-icon");
  const infoText = document.querySelector("#listing-typediv .info-text");
  
  if (infoIcon && infoText) {
    infoIcon.addEventListener("click", function () {
      infoText.classList.toggle("active");
    });
  }
  
  // Add new listing type btn
  const addNewTypeButton = document.getElementById("add-new-listing-type");
  const newTypeInput = document.getElementById("new-listing-type");
  
  if (addNewTypeButton && newTypeInput) {
    addNewTypeButton.addEventListener("click", function () {
      const newType = newTypeInput.value.trim();
      if (!newType) {
        alert("Please enter a name for the new listing type.");
        return;
      }
      
      const xhr = new XMLHttpRequest();
      const data = new FormData();
      
      data.append("action", "add_listing_type");
      data.append("taxonomy", "listing-type");
      data.append("term", newType);
      data.append("_ajax_nonce", listingInfoAjax.nonceAddListingType);
      
      console.log("Sending data:", Object.fromEntries(data.entries()));
      
      xhr.open("POST", listingInfoAjax.ajaxUrl, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          console.log("Response:", xhr.responseText);
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                alert("New listing type added successfully!");
                location.reload();
              } else {
                alert(response.data || "Failed to add new listing type.");
              }
            } catch (e) {
              alert("Error parsing response.");
              console.error(e, xhr.responseText);
            }
          } else {
            alert("Failed to send the request.");
            console.error("Status:", xhr.status, "Response:", xhr.responseText);
          }
        }
      };
      
      xhr.send(data);
    });
  }
  
  const listingTypeList = document.getElementById("listing-type-list");
  
  if (listingTypeList) {
    listingTypeList.addEventListener("click", function (e) {
      if (e.target.classList.contains("mark-primary")) {
        const termRow = e.target.closest("li");
        const termId = termRow.dataset.termId;
        const postId = document.getElementById("post_ID").value;
        
        const xhr = new XMLHttpRequest();
        const data = new FormData();
        
        data.append("action", "set_primary_listing_type");
        data.append("term_id", termId);
        data.append("post_id", postId);
        data.append("_ajax_nonce", listingInfoAjax.nonceSetPrimaryListingType);
        
        xhr.open("POST", listingInfoAjax.ajaxUrl, true);
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                // Reload the page after successfully setting the primary term
                alert("Primary listing type set successfully.");
                location.reload();
              } else {
                alert(response.data || "Failed to set primary listing type.");
              }
            } catch (e) {
              console.error("Error parsing response:", xhr.responseText);
            }
          }
        };
        xhr.send(data);
      } else if (e.target.classList.contains("mark-secondary")) {
        const termRow = e.target.closest("li");
        const termId = termRow.dataset.termId;
        const postId = document.getElementById("post_ID").value;
        
        const xhr = new XMLHttpRequest();
        const data = new FormData();
        
        data.append("action", "set_secondary_listing_type");
        data.append("term_id", termId);
        data.append("post_id", postId);
        data.append("_ajax_nonce", listingInfoAjax.nonceSetSecondaryListingType);
        
        xhr.open("POST", listingInfoAjax.ajaxUrl, true);
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                // Reload the page after successfully setting the secondary term
                alert("Secondary listing type set successfully.");
                location.reload();
              } else {
                alert(response.data || "Failed to set secondary listing type.");
              }
            } catch (e) {
              console.error("Error parsing response:", xhr.responseText);
            }
          }
        };
        xhr.send(data);
      } else if (e.target.classList.contains("remove-primary")) {
        const termRow = e.target.closest("li");
        const termId = termRow.dataset.termId;
        const postId = document.getElementById("post_ID").value;
        
        const xhr = new XMLHttpRequest();
        const data = new FormData();
        
        data.append("action", "set_primary_listing_type");
        data.append("term_id", "");
        data.append("post_id", postId);
        data.append("_ajax_nonce", listingInfoAjax.nonceSetPrimaryListingType);
        
        xhr.open("POST", listingInfoAjax.ajaxUrl, true);
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                alert("Primary label removed successfully.");
                location.reload();
              } else {
                alert(response.data || "Failed to remove primary label.");
              }
            } catch (e) {
              console.error("Error parsing response:", xhr.responseText);
            }
          }
        };
        xhr.send(data);
      } else if (e.target.classList.contains("remove-secondary")) {
        const termRow = e.target.closest("li");
        const termId = termRow.dataset.termId;
        const postId = document.getElementById("post_ID").value;
        
        const xhr = new XMLHttpRequest();
        const data = new FormData();
        
        data.append("action", "set_secondary_listing_type");
        data.append("term_id", "");
        data.append("post_id", postId);
        data.append("_ajax_nonce", listingInfoAjax.nonceSetSecondaryListingType);
        
        xhr.open("POST", listingInfoAjax.ajaxUrl, true);
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                alert("Secondary label removed successfully.");
                location.reload();
              } else {
                alert(response.data || "Failed to remove secondary label.");
              }
            } catch (e) {
              console.error("Error parsing response:", xhr.responseText);
            }
          }
        };
        xhr.send(data);
      }
    });
  }
});
