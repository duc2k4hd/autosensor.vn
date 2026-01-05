@if (isset($product->faqs) && $product->faqs->count() > 0 && $product->faqs)

    <section class="xanhworld_single_faqs">

        <h4>Câu hỏi thường gặp về {{ $product->name ?? '' }}</h4>



        @foreach ($product->faqs as $faq)

            <details>

                <summary>{{ $loop->iteration }}. {{ trim($faq->question) }}</summary>

                <p>{{ trim($faq->answer) }}</p>

            </details>

        @endforeach



    </section>

@else

    {{-- <section class="xanhworld_single_product_chatstyle">

        <h4>🛍️ Trò chuyện về sản phẩm {{ $product->name ?? '' }}</h4>



        <div class="chat">

            <div class="user"><b>👤 Khách:</b> Sản phẩm này chất lượng thế nào vậy shop?</div>

            <div class="shop"><b>🛒 Shop:</b> Dạ, sản phẩm bên em luôn cam kết chất lượng. Được chọn lọc kỹ lưỡng về chất

                liệu, form dáng và độ bền theo thời gian ạ.</div>



            <div class="user"><b>👤 Khách:</b> Dùng lâu có bền không?</div>

            <div class="shop"><b>🛒 Shop:</b> Dạ rất bền ạ! Bên em chọn chất liệu cao cấp, sản phẩm giữ form tốt, ít bị biến

                dạng hay hư hỏng khi sử dụng lâu dài.</div>



            <div class="user"><b>👤 Khách:</b> Có dễ vệ sinh hoặc bảo quản không?</div>

            <div class="shop"><b>🛒 Shop:</b> Dạ dễ lắm ạ! Chỉ cần vệ sinh đúng cách theo hướng dẫn, sản phẩm luôn như mới.

                Bên em cũng sẽ gửi kèm tips chăm sóc khi giao hàng ạ.</div>



            <div class="user"><b>👤 Khách:</b> Size/kiểu dáng có dễ chọn không?</div>

            <div class="shop"><b>🛒 Shop:</b> Dạ có bảng size và mô tả chi tiết ở phần thông tin sản phẩm. Nếu mình cần, bên

                em hỗ trợ tư vấn chọn size/phù hợp tận tình luôn ạ.</div>



            <div class="user"><b>👤 Khách:</b> Ship nhanh không shop?</div>

            <div class="shop"><b>🛒 Shop:</b> Dạ có ạ! Bên em hỗ trợ ship toàn quốc, nội thành giao siêu nhanh từ 1–2 ngày,

                ngoại tỉnh 2–4 ngày tùy khu vực ạ.</div>



            <div class="user"><b>👤 Khách:</b> Ok, cảm ơn shop nha. Để em chốt luôn.</div>

            <div class="shop"><b>🛒 Shop:</b> Dạ vâng ạ! Shop sẵn sàng phục vụ mình ngay đây. 🥰</div>

        </div>

    </section> --}}

@endif



