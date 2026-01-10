{{-- Câu hỏi 1: Môi trường làm việc --}}
<div class="autosensor_wizard_step" data-step="1">
    <h3 class="autosensor_wizard_question_title">1. Môi trường làm việc của PLC?</h3>
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

{{-- Câu hỏi 2: Số ngõ vào/ra --}}
<div class="autosensor_wizard_step" data-step="2" style="display: none;">
    <h3 class="autosensor_wizard_question_title">2. Số ngõ vào/ra cần thiết?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[io_count]" value="small" required>
            <span>Nhỏ (< 16 ngõ)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[io_count]" value="medium" required>
            <span>Trung bình (16-32 ngõ)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[io_count]" value="large" required>
            <span>Lớn (32-64 ngõ)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[io_count]" value="very_large" required>
            <span>Rất lớn (> 64 ngõ)</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 3: Loại ngõ ra --}}
<div class="autosensor_wizard_step" data-step="3" style="display: none;">
    <h3 class="autosensor_wizard_question_title">3. Loại ngõ ra cần thiết?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="relay" required>
            <span>Relay (Rơ le)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="transistor" required>
            <span>Transistor (NPN/PNP)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="analog" required>
            <span>Analog (4-20mA / 0-10V)</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 4: Điện áp --}}
<div class="autosensor_wizard_step" data-step="4" style="display: none;">
    <h3 class="autosensor_wizard_question_title">4. Điện áp nguồn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="24v" required>
            <span>24VDC</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="220v" required>
            <span>220VAC</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="dc" required>
            <span>DC (một chiều)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="ac" required>
            <span>AC (xoay chiều)</span>
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
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="m12" required>
            <span>M12</span>
        </label>
    </div>
</div>
