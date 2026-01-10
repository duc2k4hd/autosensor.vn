@php
    $categoryId = $category->id;
    $categoryName = $category->name;
    $step = 1;
@endphp

{{-- Câu hỏi 1: Môi trường làm việc (chung cho tất cả) --}}
<div class="autosensor_wizard_step" data-step="{{ $step++ }}">
    <h3 class="autosensor_wizard_question_title">1. Môi trường làm việc của {{ strtolower($categoryName) }}?</h3>
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
        @if(in_array($categoryId, [1, 3, 4, 5, 6, 7, 42])) {{-- Cảm biến, Biến tần, HMI, Rơ le, Bộ nguồn, Encoder, Bộ điều khiển --}}
        <label class="autosensor_wizard_option">
            <input type="radio" name="answers[environment]" value="outdoor" required>
            <span>Ngoài trời</span>
        </label>
        @endif
    </div>
</div>

@if($categoryId == 1) {{-- Cảm biến --}}
    {{-- Include questions riêng cho cảm biến --}}
    @php
        $sensorSteps = [
            ['step' => 2, 'title' => '2. Khoảng cách đo mong muốn?', 'name' => 'distance', 'options' => [
                ['value' => 'close', 'label' => 'Gần (< 10cm)'],
                ['value' => 'medium', 'label' => 'Trung bình (10-50cm)'],
                ['value' => 'far', 'label' => 'Xa (50-200cm)'],
                ['value' => 'very_far', 'label' => 'Rất xa (> 200cm)'],
            ]],
            ['step' => 3, 'title' => '3. Vật liệu cần phát hiện?', 'name' => 'material', 'options' => [
                ['value' => 'metal', 'label' => 'Kim loại (sắt, thép, nhôm...)'],
                ['value' => 'plastic', 'label' => 'Nhựa, PVC'],
                ['value' => 'glass', 'label' => 'Thủy tinh, kính'],
                ['value' => 'liquid', 'label' => 'Chất lỏng'],
                ['value' => 'transparent', 'label' => 'Vật liệu trong suốt'],
            ]],
            ['step' => 4, 'title' => '4. Loại ngõ ra mong muốn?', 'name' => 'output_type', 'options' => [
                ['value' => 'npn', 'label' => 'NPN'],
                ['value' => 'pnp', 'label' => 'PNP'],
                ['value' => 'relay', 'label' => 'Relay (Rơ le)'],
                ['value' => 'analog', 'label' => 'Analog (4-20mA / 0-10V)'],
                ['value' => 'digital', 'label' => 'Digital (Số)'],
            ]],
            ['step' => 5, 'title' => '5. Điện áp nguồn?', 'name' => 'voltage', 'options' => [
                ['value' => '12v', 'label' => '12VDC'],
                ['value' => '24v', 'label' => '24VDC'],
                ['value' => '220v', 'label' => '220VAC'],
                ['value' => 'dc', 'label' => 'DC (một chiều)'],
                ['value' => 'ac', 'label' => 'AC (xoay chiều)'],
            ]],
            ['step' => 6, 'title' => '6. Chuẩn kết nối mong muốn?', 'name' => 'connection', 'options' => [
                ['value' => 'cable', 'label' => 'Cáp (Cable)'],
                ['value' => 'connector', 'label' => 'Connector (Đầu nối)'],
                ['value' => 'm12', 'label' => 'M12'],
                ['value' => 'm8', 'label' => 'M8'],
                ['value' => 'terminal', 'label' => 'Terminal (Đấu dây)'],
            ]],
        ];
    @endphp
    @foreach($sensorSteps as $q)
    <div class="autosensor_wizard_step" data-step="{{ $q['step'] }}" style="display: none;">
        <h3 class="autosensor_wizard_question_title">{{ $q['title'] }}</h3>
        <div class="autosensor_wizard_options">
            @foreach($q['options'] as $option)
            <label class="autosensor_wizard_option">
                <input type="radio" name="answers[{{ $q['name'] }}]" value="{{ $option['value'] }}" required>
                <span>{{ $option['label'] }}</span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach

@elseif($categoryId == 2) {{-- PLC --}}
    @php
        $plcSteps = [
            ['step' => 2, 'title' => '2. Số ngõ vào/ra cần thiết?', 'name' => 'io_count', 'options' => [
                ['value' => 'small', 'label' => 'Nhỏ (< 16 ngõ)'],
                ['value' => 'medium', 'label' => 'Trung bình (16-32 ngõ)'],
                ['value' => 'large', 'label' => 'Lớn (32-64 ngõ)'],
                ['value' => 'very_large', 'label' => 'Rất lớn (> 64 ngõ)'],
            ]],
            ['step' => 3, 'title' => '3. Loại ngõ ra cần thiết?', 'name' => 'output_type', 'options' => [
                ['value' => 'relay', 'label' => 'Relay (Rơ le)'],
                ['value' => 'transistor', 'label' => 'Transistor (NPN/PNP)'],
                ['value' => 'analog', 'label' => 'Analog (4-20mA / 0-10V)'],
            ]],
            ['step' => 4, 'title' => '4. Điện áp nguồn?', 'name' => 'voltage', 'options' => [
                ['value' => '24v', 'label' => '24VDC'],
                ['value' => '220v', 'label' => '220VAC'],
                ['value' => 'dc', 'label' => 'DC (một chiều)'],
                ['value' => 'ac', 'label' => 'AC (xoay chiều)'],
            ]],
            ['step' => 5, 'title' => '5. Chuẩn kết nối mong muốn?', 'name' => 'connection', 'options' => [
                ['value' => 'terminal', 'label' => 'Terminal (Đấu dây)'],
                ['value' => 'connector', 'label' => 'Connector (Đầu nối)'],
                ['value' => 'm12', 'label' => 'M12'],
            ]],
        ];
    @endphp
    @foreach($plcSteps as $q)
    <div class="autosensor_wizard_step" data-step="{{ $q['step'] }}" style="display: none;">
        <h3 class="autosensor_wizard_question_title">{{ $q['title'] }}</h3>
        <div class="autosensor_wizard_options">
            @foreach($q['options'] as $option)
            <label class="autosensor_wizard_option">
                <input type="radio" name="answers[{{ $q['name'] }}]" value="{{ $option['value'] }}" required>
                <span>{{ $option['label'] }}</span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach

@elseif($categoryId == 3) {{-- Biến tần --}}
    @php
        $inverterSteps = [
            ['step' => 2, 'title' => '2. Công suất động cơ cần điều khiển?', 'name' => 'power', 'options' => [
                ['value' => 'low', 'label' => 'Nhỏ (< 1kW)'],
                ['value' => 'medium', 'label' => 'Trung bình (1-5kW)'],
                ['value' => 'high', 'label' => 'Lớn (5-15kW)'],
                ['value' => 'very_high', 'label' => 'Rất lớn (> 15kW)'],
            ]],
            ['step' => 3, 'title' => '3. Điện áp nguồn?', 'name' => 'voltage', 'options' => [
                ['value' => '220v', 'label' => '220VAC (1 pha)'],
                ['value' => '380v', 'label' => '380VAC (3 pha)'],
                ['value' => 'ac', 'label' => 'AC (xoay chiều)'],
            ]],
            ['step' => 4, 'title' => '4. Loại điều khiển mong muốn?', 'name' => 'output_type', 'options' => [
                ['value' => 'analog', 'label' => 'Analog (4-20mA / 0-10V)'],
                ['value' => 'digital', 'label' => 'Digital (Số)'],
                ['value' => 'modbus', 'label' => 'Modbus / Communication'],
            ]],
            ['step' => 5, 'title' => '5. Chuẩn kết nối mong muốn?', 'name' => 'connection', 'options' => [
                ['value' => 'terminal', 'label' => 'Terminal (Đấu dây)'],
                ['value' => 'connector', 'label' => 'Connector (Đầu nối)'],
            ]],
        ];
    @endphp
    @foreach($inverterSteps as $q)
    <div class="autosensor_wizard_step" data-step="{{ $q['step'] }}" style="display: none;">
        <h3 class="autosensor_wizard_question_title">{{ $q['title'] }}</h3>
        <div class="autosensor_wizard_options">
            @foreach($q['options'] as $option)
            <label class="autosensor_wizard_option">
                <input type="radio" name="answers[{{ $q['name'] }}]" value="{{ $option['value'] }}" required>
                <span>{{ $option['label'] }}</span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach

@else
    {{-- Questions chung cho các categories khác --}}
    @php
        $commonSteps = [];
        
        // Câu hỏi 2: Điện áp (cho hầu hết categories)
        if (in_array($categoryId, [4, 5, 6, 7, 9, 12, 42, 58, 61])) {
            $voltageOptions = [];
            if (in_array($categoryId, [4, 5, 6, 7, 9, 12, 42, 58, 61])) {
                $voltageOptions[] = ['value' => '12v', 'label' => '12VDC'];
                $voltageOptions[] = ['value' => '24v', 'label' => '24VDC'];
            }
            if (in_array($categoryId, [4, 5, 9, 12, 42])) {
                $voltageOptions[] = ['value' => '220v', 'label' => '220VAC'];
            }
            if (in_array($categoryId, [4, 5, 6, 7, 9, 12, 42, 58, 61])) {
                $voltageOptions[] = ['value' => 'dc', 'label' => 'DC (một chiều)'];
            }
            if (in_array($categoryId, [4, 5, 9, 12, 42])) {
                $voltageOptions[] = ['value' => 'ac', 'label' => 'AC (xoay chiều)'];
            }
            
            if (!empty($voltageOptions)) {
                $commonSteps[] = ['step' => 2, 'title' => '2. Điện áp nguồn?', 'name' => 'voltage', 'options' => $voltageOptions];
            }
        }
        
        // Câu hỏi 3: Loại ngõ ra (cho một số categories)
        if (in_array($categoryId, [4, 5, 7, 12, 42, 58, 61])) {
            $outputOptions = [];
            if (in_array($categoryId, [5, 7, 12, 42, 58, 61])) {
                $outputOptions[] = ['value' => 'npn', 'label' => 'NPN'];
                $outputOptions[] = ['value' => 'pnp', 'label' => 'PNP'];
            }
            if (in_array($categoryId, [5, 12, 42])) {
                $outputOptions[] = ['value' => 'relay', 'label' => 'Relay (Rơ le)'];
            }
            if (in_array($categoryId, [4, 7, 42, 58, 61])) {
                $outputOptions[] = ['value' => 'analog', 'label' => 'Analog (4-20mA / 0-10V)'];
                $outputOptions[] = ['value' => 'digital', 'label' => 'Digital (Số)'];
            }
            
            if (!empty($outputOptions)) {
                $stepNum = count($commonSteps) + 2;
                $commonSteps[] = ['step' => $stepNum, 'title' => $stepNum . '. Loại ngõ ra mong muốn?', 'name' => 'output_type', 'options' => $outputOptions];
            }
        }
        
        // Câu hỏi cuối: Chuẩn kết nối
        if (in_array($categoryId, [1, 4, 5, 6, 7, 9, 12, 42, 58, 61])) {
            $connectionOptions = [];
            if (in_array($categoryId, [1, 5, 7, 9, 12, 42, 58, 61])) {
                $connectionOptions[] = ['value' => 'cable', 'label' => 'Cáp (Cable)'];
            }
            if (in_array($categoryId, [1, 4, 5, 7, 9, 12, 42, 58, 61])) {
                $connectionOptions[] = ['value' => 'connector', 'label' => 'Connector (Đầu nối)'];
            }
            if (in_array($categoryId, [1, 7])) {
                $connectionOptions[] = ['value' => 'm12', 'label' => 'M12'];
                $connectionOptions[] = ['value' => 'm8', 'label' => 'M8'];
            }
            if (in_array($categoryId, [1, 4, 5, 6, 9, 12, 42, 58, 61])) {
                $connectionOptions[] = ['value' => 'terminal', 'label' => 'Terminal (Đấu dây)'];
            }
            
            if (!empty($connectionOptions)) {
                $stepNum = count($commonSteps) + 2;
                $commonSteps[] = ['step' => $stepNum, 'title' => $stepNum . '. Chuẩn kết nối mong muốn?', 'name' => 'connection', 'options' => $connectionOptions];
            }
        }
    @endphp
    
    @foreach($commonSteps as $q)
    <div class="autosensor_wizard_step" data-step="{{ $q['step'] }}" style="display: none;">
        <h3 class="autosensor_wizard_question_title">{{ $q['title'] }}</h3>
        <div class="autosensor_wizard_options">
            @foreach($q['options'] as $option)
            <label class="autosensor_wizard_option">
                <input type="radio" name="answers[{{ $q['name'] }}]" value="{{ $option['value'] }}" required>
                <span>{{ $option['label'] }}</span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach
@endif
