(function() {
    'use strict';
    
    let currentStep = 1;
    let totalSteps = 0;
    let form, steps, prevBtn, nextBtn, submitBtn, progressBar, loadingDiv;

    // Khởi tạo các element sau khi DOM ready
    function initElements() {
        form = document.getElementById('wizard-form');
        steps = document.querySelectorAll('.autosensor_wizard_step');
        prevBtn = document.getElementById('wizard-prev-btn');
        nextBtn = document.getElementById('wizard-next-btn');
        submitBtn = document.getElementById('wizard-submit-btn');
        progressBar = document.getElementById('wizard-progress-bar');
        loadingDiv = document.getElementById('wizard-loading');

        // Đếm tổng số bước
        totalSteps = steps.length;
    }

    // Cập nhật UI
    function updateUI() {
        // Hiển thị/ẩn các bước
        steps.forEach((step, index) => {
            step.style.display = (index + 1 === currentStep) ? 'block' : 'none';
        });

        // Cập nhật progress bar
        const progress = (currentStep / totalSteps) * 100;
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }

        // Hiển thị/ẩn nút
        if (prevBtn) {
            prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
        }
        if (nextBtn) {
            nextBtn.style.display = currentStep < totalSteps ? 'block' : 'none';
        }
        if (submitBtn) {
            submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
        }
    }

    // Kiểm tra bước hiện tại có hợp lệ không
    function isCurrentStepValid() {
        const currentStepElement = document.querySelector(`.autosensor_wizard_step[data-step="${currentStep}"]`);
        if (!currentStepElement) {
            return false;
        }

        // Tìm tất cả các nhóm radio trong step hiện tại (theo name)
        const radioNames = new Set();
        const radioInputs = currentStepElement.querySelectorAll('input[type="radio"]');
        
        if (radioInputs.length === 0) {
            return true; // Không có radio thì coi như hợp lệ
        }
        
        radioInputs.forEach(input => {
            radioNames.add(input.name);
        });

        // Kiểm tra từng nhóm radio có được chọn không
        for (const name of radioNames) {
            const radios = currentStepElement.querySelectorAll(`input[type="radio"][name="${name}"]`);
            const isChecked = Array.from(radios).some(radio => radio.checked);
            if (!isChecked) {
                return false;
            }
        }

        // Kiểm tra các input khác (nếu có)
        const otherInputs = currentStepElement.querySelectorAll('input[required]:not([type="radio"])');
        for (const input of otherInputs) {
            if (!input.value || !input.value.trim()) {
                return false;
            }
        }

        return true;
    }


    // Khởi tạo
    function init() {
        initElements();
        
        if (!form || steps.length === 0) {
            return;
        }
        
        // Setup event listeners
        setupEventListeners();
        
        // Cập nhật UI ban đầu
        updateUI();
    }

    function setupEventListeners() {
        // Nút Next
        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isValid = isCurrentStepValid();
                
                if (isValid) {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        updateUI();
                        // Scroll to top
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } else {
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Vui lòng chọn một phương án trước khi tiếp tục', 'warning', 3000);
                    } else {
                        alert('Vui lòng chọn một phương án trước khi tiếp tục');
                    }
                }
            });
        }

        // Nút Prev
        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (currentStep > 1) {
                    currentStep--;
                    updateUI();
                    // Scroll to top
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }

        // Submit form
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!isCurrentStepValid()) {
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Vui lòng hoàn thành tất cả các câu hỏi', 'warning', 3000);
                    } else {
                        alert('Vui lòng hoàn thành tất cả các câu hỏi');
                    }
                    return;
                }

                // Ẩn form, hiện loading
                if (form) form.style.display = 'none';
                if (loadingDiv) loadingDiv.style.display = 'block';

                // Lấy dữ liệu form
                const formData = new FormData(form);
                const data = {
                    category_id: parseInt(formData.get('category_id')),
                    answers: {}
                };

                // Lấy tất cả answers
                formData.forEach((value, key) => {
                    if (key.startsWith('answers[')) {
                        const match = key.match(/answers\[(.*?)\]/);
                        if (match) {
                            const answerKey = match[1];
                            data.answers[answerKey] = value;
                        }
                    }
                });

                // Gửi request
                const processUrl = form.dataset.processUrl || '/huong-dan-chon/process';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                
                fetch(processUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.session_id) {
                        // Redirect to result page
                        window.location.href = `/huong-dan-chon/ket-qua/${result.session_id}`;
                    } else {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(result.message || 'Có lỗi xảy ra. Vui lòng thử lại.', 'error', 4000);
                        } else {
                            alert(result.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                        }
                        if (form) form.style.display = 'block';
                        if (loadingDiv) loadingDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Có lỗi xảy ra khi xử lý. Vui lòng thử lại.', 'error', 4000);
                    } else {
                        alert('Có lỗi xảy ra khi xử lý. Vui lòng thử lại.');
                    }
                    if (form) form.style.display = 'block';
                    if (loadingDiv) loadingDiv.style.display = 'none';
                });
            });
        }
    }

    // Khởi tạo khi DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM đã sẵn sàng
        setTimeout(init, 100);
    }
})();
