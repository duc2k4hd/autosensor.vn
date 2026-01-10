{{-- Câu hỏi 1: Môi trường làm việc --}}
<div class="autosensor_wizard_step" data-step="1">
    <h3 class="autosensor_wizard_question_title">1. Môi trường làm việc của biến tần?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[environment]" value="normal" required>
            <span>Môi trường bình thường (trong nhà, sạch sẽ)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[environment]" value="dusty" required>
            <span>Môi trường nhiều bụi</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[environment]" value="humid" required>
            <span>Môi trường ẩm ướt</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[environment]" value="high_temp" required>
            <span>Môi trường nhiệt độ cao</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 2: Công suất động cơ --}}
<div class="autosensor_wizard_step" data-step="2" style="display: none;">
    <h3 class="autosensor_wizard_question_title">2. Công suất động cơ cần điều khiển?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[power]" value="low" required>
            <span>Nhỏ (< 1kW)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[power]" value="medium" required>
            <span>Trung bình (1-5kW)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[power]" value="high" required>
            <span>Lớn (5-15kW)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[power]" value="very_high" required>
            <span>Rất lớn (> 15kW)</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 3: Điện áp --}}
<div class="autosensor_wizard_step" data-step="3" style="display: none;">
    <h3 class="autosensor_wizard_question_title">3. Điện áp nguồn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="220v" required>
            <span>220VAC (1 pha)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="380v" required>
            <span>380VAC (3 pha)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="ac" required>
            <span>AC (xoay chiều)</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 4: Loại ngõ ra --}}
<div class="autosensor_wizard_step" data-step="4" style="display: none;">
    <h3 class="autosensor_wizard_question_title">4. Loại điều khiển mong muốn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="analog" required>
            <span>Analog (4-20mA / 0-10V)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="digital" required>
            <span>Digital (Số)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="modbus" required>
            <span>Modbus / Communication</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 5: Chuẩn kết nối --}}
<div class="autosensor_wizard_step" data-step="5" style="display: none;">
    <h3 class="autosensor_wizard_question_title">5. Chuẩn kết nối mong muốn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="terminal" required>
            <span>Terminal (Đấu dây)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="connector" required>
            <span>Connector (Đầu nối)</span>
        </label>
    </div>
</div>
