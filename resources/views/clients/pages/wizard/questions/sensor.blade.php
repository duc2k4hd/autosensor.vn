{{-- Câu hỏi 1: Môi trường làm việc --}}
<div class="autosensor_wizard_step" data-step="1">
    <h3 class="autosensor_wizard_question_title">1. Môi trường làm việc của cảm biến?</h3>
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
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[environment]" value="outdoor" required>
            <span>Ngoài trời</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 2: Khoảng cách đo --}}
<div class="autosensor_wizard_step" data-step="2" style="display: none;">
    <h3 class="autosensor_wizard_question_title">2. Khoảng cách đo mong muốn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[distance]" value="close" required>
            <span>Gần (< 10cm)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[distance]" value="medium" required>
            <span>Trung bình (10-50cm)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[distance]" value="far" required>
            <span>Xa (50-200cm)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[distance]" value="very_far" required>
            <span>Rất xa (> 200cm)</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 3: Vật liệu cần phát hiện --}}
<div class="autosensor_wizard_step" data-step="3" style="display: none;">
    <h3 class="autosensor_wizard_question_title">3. Vật liệu cần phát hiện?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[material]" value="metal" required>
            <span>Kim loại (sắt, thép, nhôm...)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[material]" value="plastic" required>
            <span>Nhựa, PVC</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[material]" value="glass" required>
            <span>Thủy tinh, kính</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[material]" value="liquid" required>
            <span>Chất lỏng</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[material]" value="transparent" required>
            <span>Vật liệu trong suốt</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 4: Loại ngõ ra --}}
<div class="autosensor_wizard_step" data-step="4" style="display: none;">
    <h3 class="autosensor_wizard_question_title">4. Loại ngõ ra mong muốn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="npn" required>
            <span>NPN</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="pnp" required>
            <span>PNP</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="relay" required>
            <span>Relay (Rơ le)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="analog" required>
            <span>Analog (4-20mA / 0-10V)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[output_type]" value="digital" required>
            <span>Digital (Số)</span>
        </label>
    </div>
</div>

{{-- Câu hỏi 5: Điện áp --}}
<div class="autosensor_wizard_step" data-step="5" style="display: none;">
    <h3 class="autosensor_wizard_question_title">5. Điện áp nguồn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[voltage]" value="12v" required>
            <span>12VDC</span>
        </label>
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

{{-- Câu hỏi 6: Chuẩn kết nối --}}
<div class="autosensor_wizard_step" data-step="6" style="display: none;">
    <h3 class="autosensor_wizard_question_title">6. Chuẩn kết nối mong muốn?</h3>
    <div class="autosensor_wizard_options">
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="cable" required>
            <span>Cáp (Cable)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="connector" required>
            <span>Connector (Đầu nối)</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="m12" required>
            <span>M12</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="m8" required>
            <span>M8</span>
        </label>
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[connection]" value="terminal" required>
            <span>Terminal (Đấu dây)</span>
        </label>
    </div>
</div>
