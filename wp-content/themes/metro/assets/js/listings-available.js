(() => {
    const initializeBlock = () => {
        const newAvailableDate = document.querySelector('#new-available-date');
        const updateButton = document.querySelector('#replace-dates');
        const selectAllCheckbox = document.querySelector('#select-all');
        const allCheckboxes = document.querySelectorAll('.meta_ids');
        const responseMessage = document.querySelector('#availability_updated');
        if (newAvailableDate && updateButton) {
            newAvailableDate.addEventListener('change', (e) => {
                if (e.target.value !== '') {
                    updateButton.removeAttribute('disabled');
                }
                else {
                    updateButton.setAttribute('disabled', 'true');
                }
            })
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('input', (e) => {
                toggleCheckboxes(e.target.checked)
            })
        }

        if (updateButton && newAvailableDate && allCheckboxes && responseMessage) {
            const responseText = responseMessage.querySelector('p');

            updateButton.addEventListener('click', (e) => {
                const date = newAvailableDate.value;
                const ids = getAllCheckedCheckboxes();
                let formData = new FormData();
                formData.append('action', 'replace_available_dates');
                formData.append('date', date);
                formData.append('ids', ids);

                fetch(ajaxurl, {
                    method: "POST",
                    credentials: "same-origin",
                    body: formData
                })
                    .then(response => response.json())
                    .then(json => {
                        if (!isNaN(parseFloat(json)) && isFinite(json)) {
                            responseText.innerHTML = `<strong>${json} Listings Updated. Reload page to see changes.</strong>`;
                            responseMessage.style.display = 'block';
                        }
                        else {
                            reponseMessage.innerHTML = `DB Response: ${json}; No listings were updated.`;
                            responseMessage.style.display = 'block';
                        }
                    })
            })
        }

        const getAllCheckedCheckboxes = () => {
            if (allCheckboxes) {
                let result = Array.from(allCheckboxes).filter(item => item.checked);
                result = result.map(item => item.id);
                return result;
            }
        }

        const toggleCheckboxes = (boolean) => {
            if (allCheckboxes) {
                allCheckboxes.forEach(checkbox => {
                    checkbox.checked = boolean
                })
            }
        }



        const squareFeetField = document.querySelector('[data-name="square_feet"]');
        const rentSfField = document.querySelector('[data-name="rent_sf"]');
        const monthlyRentField = document.querySelector('[data-name="monthly_rent"]');

        if (squareFeetField && rentSfField && monthlyRentField) {
            const squareFeetInput = squareFeetField.querySelector('input');
            const rentSfInput = rentSfField.querySelector('input');
            const monthlyRentInput = monthlyRentField.querySelector('input');

            squareFeetInput.addEventListener('input', (e) => {
                calculateMonthlyRent(squareFeetInput, rentSfInput, monthlyRentInput);
            });

            rentSfInput.addEventListener('input', (e) => {
                calculateMonthlyRent(squareFeetInput, rentSfInput, monthlyRentInput);
            })

            const calculateMonthlyRent = (aField, bField, resultField) => {
                if (aField.value !== '' && bField.value !== '') {
                    const a = parseFloat(aField.value);
                    const b = parseFloat(bField.value);
                    resultField.value = a * b / 12;
                }
                else {
                    resultField.value = 0
                }
            }
        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        initializeBlock()
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            initializeBlock()
        })
    }
})()