<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('categories')->upsert([
            // =========================
            // CẤP 1 – DANH MỤC CHÍNH
            // =========================

            [
                'id' => 1,
                'parent_id' => null,
                'name' => 'Cảm biến',
                'slug' => 'cam-bien',
                'description' => 'Danh mục cảm biến công nghiệp dùng trong tự động hóa nhà máy và dây chuyền sản xuất.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến chính hãng - chất lượng, Bảng giá 2026',
                    'meta_description' => 'Chuyên cung cấp cảm biến công nghiệp gồm cảm biến quang, tiệm cận, áp suất, nhiệt độ, mức, lưu lượng dùng cho nhà máy và tủ điện. Sản phẩm chính hãng, thông số rõ ràng, hỗ trợ kỹ thuật chuyên sâu, tư vấn chọn đúng model và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 2,
                'parent_id' => null,
                'name' => 'PLC',
                'slug' => 'plc',
                'description' => 'Bộ điều khiển PLC dùng cho hệ thống điều khiển và tự động hóa công nghiệp.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'PLC chính hãng - giá tốt, Báo giá 2026',
                    'meta_description' => 'Cung cấp PLC công nghiệp dùng điều khiển máy móc, dây chuyền và hệ thống tự động hóa. Hỗ trợ chọn CPU, I/O, lập trình và đấu nối theo ứng dụng thực tế. Phù hợp tủ điện và nhà máy sản xuất – liên hệ để được tư vấn chi tiết.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 3,
                'parent_id' => null,
                'name' => 'Biến tần',
                'slug' => 'bien-tan',
                'description' => 'Biến tần điều khiển tốc độ động cơ AC trong công nghiệp.',
                'image' => null,
                'order' => 3,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Biến tần chính hãng, Tư vấn kỹ thuật, Bảng giá 2026',
                    'meta_description' => 'Biến tần công nghiệp dùng điều khiển tốc độ động cơ AC cho máy móc và dây chuyền sản xuất. Giúp vận hành ổn định, tiết kiệm điện năng, dễ cài đặt. Hỗ trợ tư vấn chọn biến tần đúng công suất và ứng dụng – xem chi tiết ngay.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 4,
                'parent_id' => null,
                'name' => 'HMI',
                'slug' => 'hmi',
                'description' => 'Màn hình HMI dùng hiển thị, giám sát và điều khiển hệ thống tự động hóa công nghiệp.',
                'image' => null,
                'order' => 4,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Màn hình HMI chính hãng – Hiển thị PLC, Bảng giá 2026',
                    'meta_description' => 'Màn hình HMI công nghiệp dùng hiển thị và điều khiển PLC, máy móc và dây chuyền sản xuất. Giao diện trực quan, dễ lập trình, hỗ trợ nhiều chuẩn truyền thông. Phù hợp tủ điện và hệ thống tự động hóa.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 5,
                'parent_id' => null,
                'name' => 'Rơ le',
                'slug' => 'ro-le',
                'description' => 'Danh mục rơ le công nghiệp gồm relay trung gian, relay thời gian và relay bán dẫn dùng trong tủ điện.',
                'image' => null,
                'order' => 5,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Rơ le chính hãng – Relay trung gian, SSR, Giá 2026',
                    'meta_description' => 'Cung cấp rơ le công nghiệp gồm relay trung gian, relay thời gian và SSR dùng trong tủ điện và hệ thống điều khiển. Hoạt động ổn định, dễ lắp đặt, tương thích PLC và HMI. Hỗ trợ tư vấn chọn đúng loại.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 6,
                'parent_id' => null,
                'name' => 'Bộ nguồn',
                'slug' => 'bo-nguon',
                'description' => 'Bộ nguồn công nghiệp dùng cấp điện ổn định cho PLC, HMI, cảm biến và thiết bị trong tủ điện.',
                'image' => null,
                'order' => 6,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ nguồn công nghiệp 24VDC chính hãng – Giá tốt 2026',
                    'meta_description' => 'Bộ nguồn công nghiệp 24VDC dùng cấp điện cho PLC, HMI, cảm biến và thiết bị điều khiển. Hoạt động ổn định, tuổi thọ cao, dễ lắp DIN rail. Phù hợp nhà máy và hệ thống tự động hóa.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 7,
                'parent_id' => null,
                'name' => 'Encoder',
                'slug' => 'encoder',
                'description' => 'Encoder công nghiệp dùng đo vị trí, tốc độ và vòng quay cho động cơ và máy móc.',
                'image' => null,
                'order' => 7,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Encoder chính hãng – Đo vị trí, tốc độ, Bảng giá 2026',
                    'meta_description' => 'Encoder công nghiệp dùng đo vị trí, tốc độ và vòng quay cho động cơ servo và máy móc tự động. Độ chính xác cao, tín hiệu ổn định, dễ tích hợp PLC và bộ điều khiển.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 8,
                'parent_id' => null,
                'name' => 'Phụ kiện cảm biến',
                'slug' => 'phu-kien-cam-bien',
                'description' => 'Danh mục phụ kiện hỗ trợ lắp đặt và kết nối cảm biến công nghiệp.',
                'image' => null,
                'order' => 8,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Phụ kiện cảm biến chính hãng – Cáp, gá lắp, đầu nối 2026',
                    'meta_description' => 'Phụ kiện cảm biến công nghiệp gồm cáp kết nối, gá lắp, gương phản xạ và đầu nối. Giúp lắp đặt cảm biến gọn gàng, vận hành ổn định trong môi trường công nghiệp.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 9,
                'parent_id' => null,
                'name' => 'Aptomat',
                'slug' => 'aptomat',
                'description' => 'Danh mục aptomat dùng bảo vệ quá tải, ngắn mạch và rò điện trong hệ thống điện.',
                'image' => null,
                'order' => 9,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Aptomat chính hãng – An toàn điện, Bảng giá 2026',
                    'meta_description' => 'Cung cấp aptomat chính hãng gồm MCB, MCCB, ACB, RCCB, RCBO dùng bảo vệ quá tải, ngắn mạch và chống giật. Thông số rõ ràng, phù hợp tủ điện dân dụng và công nghiệp.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 10,
                'parent_id' => null,
                'name' => 'Dây điện - Cáp điện',
                'slug' => 'day-dien',
                'description' => 'Danh mục dây điện và cáp điện dùng trong tủ điện và hệ thống công nghiệp.',
                'image' => null,
                'order' => 10,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Dây điện, cáp điện công nghiệp chính hãng – Giá 2026',
                    'meta_description' => 'Cung cấp dây điện và cáp điện công nghiệp dùng cho tủ điện, máy móc và hệ thống tự động hóa. Đủ tiết diện, tiêu chuẩn rõ ràng, phù hợp lắp đặt công nghiệp.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 11,
                'parent_id' => null,
                'name' => 'Máng điện nhựa',
                'slug' => 'mang-dien-nhua',
                'description' => 'Máng điện nhựa dùng đi dây gọn gàng và bảo vệ cáp trong tủ điện.',
                'image' => null,
                'order' => 11,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Máng điện nhựa chính hãng đi dây tủ điện, Bảng giá 2026',
                    'meta_description' => 'Máng điện nhựa dùng đi dây trong tủ điện, giúp bảo vệ cáp và thi công gọn gàng. Nhiều kích thước, dễ lắp đặt, phù hợp hệ thống điện công nghiệp.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 12,
                'parent_id' => null,
                'name' => 'Công tắc, Đèn báo và Còi báo',
                'slug' => 'cong-tac-den-bao-va-coi-bao',
                'description' => 'Danh mục công tắc, đèn báo và còi báo dùng trong tủ điện và hệ thống điều khiển.',
                'image' => null,
                'order' => 12,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Công tắc, đèn báo, còi báo chính hãng – Bảng giá 2026',
                    'meta_description' => 'Cung cấp công tắc, đèn báo và còi báo công nghiệp dùng trong tủ điện và hệ thống điều khiển. Dễ lắp đặt, độ bền cao, phù hợp môi trường nhà máy.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 42,
                'parent_id' => null,
                'name' => 'Bộ điều khiển',
                'slug' => 'bo-dieu-khien',
                'description' => 'Danh mục bộ điều khiển công nghiệp dùng giám sát và điều khiển các thông số trong hệ thống tự động hóa.',
                'image' => null,
                'order' => 13,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển công nghiệp chính hãng – Bảng giá 2026',
                    'meta_description' => 'Cung cấp bộ điều khiển công nghiệp gồm điều khiển nhiệt độ, áp suất, mức, PID, thời gian và dòng điện. Thông số rõ ràng, dễ cài đặt, phù hợp tủ điện và nhà máy sản xuất.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC CẢM BIẾN
            // =========================

            [
                'id' => 13,
                'parent_id' => 1,
                'name' => 'Cảm biến quang',
                'slug' => 'cam-bien-quang',
                'description' => 'Cảm biến quang phát hiện vật thể trong dây chuyền sản xuất.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến quang chính hãng – Phát hiện vật thể, Bảng giá 2026',
                    'meta_description' => 'Cảm biến quang gồm loại thu phát, phản xạ gương và khuếch tán dùng phát hiện vật thể chính xác. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, thông số rõ ràng, hỗ trợ chọn đúng model và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 14,
                'parent_id' => 1,
                'name' => 'Cảm biến tiệm cận',
                'slug' => 'cam-bien-tiem-can',
                'description' => 'Cảm biến tiệm cận phát hiện kim loại và phi kim.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến tiệm cận chính hãng – NPN, PNP, M12, M18 - Bảng giá 2026',
                    'meta_description' => 'Cảm biến tiệm cận dùng phát hiện kim loại và phi kim, có nhiều chuẩn NPN, PNP, M12, M18. AutoSensor Việt Nam hỗ trợ tư vấn kỹ thuật, chọn đúng loại theo ứng dụng và cung cấp bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 15,
                'parent_id' => 1,
                'name' => 'Cảm biến áp suất',
                'slug' => 'cam-bien-ap-suat',
                'description' => 'Cảm biến đo áp suất khí, dầu và chất lỏng.',
                'image' => null,
                'order' => 3,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến áp suất chính hãng – Đo khí, dầu, chất lỏng - Bảng giá 2026',
                    'meta_description' => 'Cảm biến áp suất dùng đo áp suất khí nén, dầu và chất lỏng với độ ổn định cao. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ chọn dải đo phù hợp và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 16,
                'parent_id' => 1,
                'name' => 'Cảm biến nhiệt độ',
                'slug' => 'cam-bien-nhiet-do',
                'description' => 'Cảm biến nhiệt độ đo nhiệt trong lò và máy móc.',
                'image' => null,
                'order' => 4,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến nhiệt độ PT100, Can nhiệt – Bảng giá 2026',
                    'meta_description' => 'Cảm biến nhiệt độ gồm PT100, can nhiệt K, J dùng đo nhiệt chính xác. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, dễ thay thế, thông số rõ ràng và hỗ trợ tư vấn kỹ thuật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 17,
                'parent_id' => 1,
                'name' => 'Cảm biến mức',
                'slug' => 'cam-bien-muc',
                'description' => 'Cảm biến đo mức chất lỏng, bột và hạt.',
                'image' => null,
                'order' => 5,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến mức chính hãng – Đo mức nước, Bảng giá 2026',
                    'meta_description' => 'Cảm biến mức dùng đo mức nước, dầu, hóa chất, bột và hạt trong bồn chứa. AutoSensor Việt Nam hỗ trợ tư vấn chọn loại phù hợp ứng dụng và cung cấp bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 18,
                'parent_id' => 1,
                'name' => 'Cảm biến lưu lượng',
                'slug' => 'cam-bien-luu-luong',
                'description' => 'Cảm biến đo lưu lượng chất lỏng và khí.',
                'image' => null,
                'order' => 6,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến lưu lượng chính hãng – Đo nước, khí, chất lỏng - Bảng giá 2026',
                    'meta_description' => 'Cảm biến lưu lượng dùng đo lưu lượng nước, khí và chất lỏng với độ chính xác cao. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, thông số rõ ràng và hỗ trợ kỹ thuật chi tiết.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 19,
                'parent_id' => 1,
                'name' => 'Cảm biến dòng điện',
                'slug' => 'cam-bien-dong-dien',
                'description' => 'Cảm biến đo dòng AC và DC trong tủ điện.',
                'image' => null,
                'order' => 7,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến dòng điện AC DC – Giám sát tải, Bảng giá 2026',
                    'meta_description' => 'Cảm biến dòng điện dùng đo dòng AC và DC trong tủ điện, giúp giám sát tải an toàn. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, tư vấn chọn đúng loại và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 20,
                'parent_id' => 1,
                'name' => 'Cảm biến hồng ngoại',
                'slug' => 'cam-bien-hong-ngoai',
                'description' => 'Cảm biến hồng ngoại phát hiện vật thể và chuyển động.',
                'image' => null,
                'order' => 8,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến hồng ngoại chính hãng – Phát hiện vật thể, Bảng giá 2026',
                    'meta_description' => 'Cảm biến hồng ngoại dùng phát hiện vật thể và chuyển động trong nhiều môi trường khác nhau. AutoSensor Việt Nam hỗ trợ tư vấn chọn loại phù hợp và cung cấp sản phẩm chính hãng.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 21,
                'parent_id' => 1,
                'name' => 'Cảm biến hình ảnh',
                'slug' => 'cam-bien-hinh-anh',
                'description' => 'Cảm biến hình ảnh và vision sensor trong công nghiệp.',
                'image' => null,
                'order' => 9,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến hình ảnh chính hãng, Bảng giá 2026',
                    'meta_description' => 'Cảm biến hình ảnh và vision sensor dùng kiểm tra, nhận diện và đo lường sản phẩm. AutoSensor Việt Nam cung cấp giải pháp phù hợp từng ứng dụng và hỗ trợ tư vấn kỹ thuật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 22,
                'parent_id' => 1,
                'name' => 'Cảm biến vùng',
                'slug' => 'cam-bien-vung',
                'description' => 'Cảm biến vùng an toàn phát hiện vật thể và chuyển động.',
                'image' => null,
                'order' => 10,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến vùng an toàn chính hãng, Bảng giá 2026',
                    'meta_description' => 'Cảm biến vùng an toàn dùng phát hiện vật thể và bảo vệ khu vực nguy hiểm. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ tư vấn lắp đặt và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 23,
                'parent_id' => 1,
                'name' => 'Cảm biến cửa',
                'slug' => 'cam-bien-cua',
                'description' => 'Cảm biến cửa phát hiện trạng thái đóng mở cửa và vị trí.',
                'image' => null,
                'order' => 11,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cảm biến cửa chính hãng – Giám sát trạng thái đóng mở, Bảng giá 2026',
                    'meta_description' => 'Cảm biến cửa dùng phát hiện trạng thái đóng mở trong hệ thống giám sát và an toàn. AutoSensor Việt Nam hỗ trợ tư vấn chọn model phù hợp và cung cấp bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC PLC
            // =========================

            [
                'id' => 24,
                'parent_id' => 2,
                'name' => 'Bộ lập trình PLC',
                'slug' => 'bo-lap-trinh-plc',
                'description' => 'Bộ lập trình PLC dùng cấu hình, nạp chương trình và vận hành hệ thống điều khiển.',
                'image' => null,
                'order' => 0,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ lập trình PLC chính hãng, cấu hình và nạp chương trình - Bảng giá 2026',
                    'meta_description' => 'Bộ lập trình PLC dùng cấu hình và nạp chương trình cho hệ thống điều khiển. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ kỹ thuật, tư vấn chọn đúng model và cập nhật bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 25,
                'parent_id' => 2,
                'name' => 'Cáp lập trình PLC',
                'slug' => 'cap-lap-trinh-plc',
                'description' => 'Cáp lập trình PLC dùng kết nối máy tính với PLC để nạp và giám sát chương trình.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cáp lập trình PLC chính hãng, kết nối PC với PLC, Bảng giá 2026',
                    'meta_description' => 'Cáp lập trình PLC dùng kết nối máy tính với PLC để nạp và giám sát chương trình. AutoSensor Việt Nam hỗ trợ tư vấn đúng chuẩn cáp theo hãng và cung cấp bảng giá cập nhật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC BIẾN TẦN
            // =========================

            [
                'id' => 26,
                'parent_id' => 3,
                'name' => 'Biến tần 1 pha',
                'slug' => 'bien-tan-1-pha',
                'description' => 'Biến tần 1 pha dùng điều khiển tốc độ động cơ cho các ứng dụng tải nhỏ.',
                'image' => null,
                'order' => 0,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Biến tần 1 pha chính hãng, điều khiển động cơ tải nhỏ - Bảng giá 2026',
                    'meta_description' => 'Biến tần 1 pha dùng điều khiển tốc độ động cơ cho quạt, bơm và máy nhỏ. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, thông số rõ ràng, hỗ trợ chọn đúng công suất và cập nhật bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 27,
                'parent_id' => 3,
                'name' => 'Biến tần 3 pha',
                'slug' => 'bien-tan-3-pha',
                'description' => 'Biến tần 3 pha dùng điều khiển tốc độ động cơ trong hệ thống sản xuất.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Biến tần 3 pha chính hãng, điều khiển động cơ ổn định, Bảng giá 2026',
                    'meta_description' => 'Biến tần 3 pha dùng điều khiển tốc độ động cơ cho máy móc và dây chuyền. AutoSensor Việt Nam hỗ trợ tư vấn chọn biến tần đúng tải, đúng ứng dụng và cung cấp bảng giá cập nhật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC RƠ LE
            // =========================

            [
                'id' => 28,
                'parent_id' => 5,
                'name' => 'Rơ le trung gian',
                'slug' => 'ro-le-trung-gian',
                'description' => 'Rơ le trung gian dùng khuếch đại tín hiệu và cách ly mạch điều khiển.',
                'image' => null,
                'order' => 0,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Rơ le trung gian chính hãng, đóng cắt ổn định, Bảng giá 2026',
                    'meta_description' => 'Rơ le trung gian dùng khuếch đại tín hiệu và cách ly mạch điều khiển trong tủ điện. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, dễ lắp đặt, hỗ trợ tư vấn chọn đúng loại và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 29,
                'parent_id' => 5,
                'name' => 'Rơ le thời gian',
                'slug' => 'ro-le-thoi-gian',
                'description' => 'Rơ le thời gian dùng tạo trễ đóng cắt trong hệ thống điều khiển.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Rơ le thời gian Timer chính hãng, tạo trễ chính xác - Bảng giá 2026',
                    'meta_description' => 'Rơ le thời gian (Timer) dùng tạo trễ đóng cắt trong hệ thống điều khiển. AutoSensor Việt Nam hỗ trợ tư vấn chọn dải thời gian phù hợp và cung cấp bảng giá cập nhật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 30,
                'parent_id' => 5,
                'name' => 'Rơ le bán dẫn',
                'slug' => 'ro-le-ban-dan',
                'description' => 'Rơ le bán dẫn SSR dùng đóng cắt nhanh và không tiếp điểm cơ.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Rơ le bán dẫn SSR chính hãng, đóng cắt nhanh, Bảng giá 2026',
                    'meta_description' => 'Rơ le bán dẫn SSR dùng đóng cắt nhanh, không tiếp điểm cơ, tuổi thọ cao. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, tư vấn chọn đúng dòng tải và báo giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 31,
                'parent_id' => 5,
                'name' => 'Rơ le an toàn',
                'slug' => 'ro-le-an-toan',
                'description' => 'Rơ le an toàn dùng giám sát và đảm bảo an toàn cho hệ thống và người vận hành.',
                'image' => null,
                'order' => 3,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Rơ le an toàn chính hãng, bảo vệ hệ thống, Bảng giá 2026',
                    'meta_description' => 'Rơ le an toàn dùng giám sát mạch an toàn và bảo vệ người vận hành. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ tư vấn ứng dụng và cập nhật bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC BỘ NGUỒN
            // =========================

            [
                'id' => 32,
                'parent_id' => 6,
                'name' => 'Bộ nguồn tổ ong',
                'slug' => 'bo-nguon-to-ong',
                'description' => 'Bộ nguồn tổ ong dùng cấp điện ổn định cho thiết bị điều khiển và tủ điện.',
                'image' => null,
                'order' => 0,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ nguồn tổ ong chính hãng, cấp nguồn ổn định - Bảng giá 2026',
                    'meta_description' => 'Bộ nguồn tổ ong dùng cấp điện ổn định cho PLC, HMI, cảm biến và thiết bị điều khiển. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, thông số rõ ràng, hỗ trợ tư vấn chọn đúng công suất và cập nhật bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 33,
                'parent_id' => 6,
                'name' => 'Bộ chuyển nguồn',
                'slug' => 'bo-chuyen-nguon',
                'description' => 'Bộ chuyển nguồn dùng chuyển đổi và cấp nguồn liên tục cho hệ thống điện.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ chuyển nguồn tự động chính hãng, cấp nguồn liên tục, Bảng giá 2026',
                    'meta_description' => 'Bộ chuyển nguồn dùng chuyển đổi nguồn điện tự động, đảm bảo cấp điện liên tục cho hệ thống. AutoSensor Việt Nam hỗ trợ tư vấn lựa chọn giải pháp phù hợp và cung cấp bảng giá cập nhật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC PHỤ KIỆN CẢM BIẾN
            // =========================

            [
                'id' => 34,
                'parent_id' => 8,
                'name' => 'Gá cảm biến',
                'slug' => 'ga-cam-bien',
                'description' => 'Gá cảm biến dùng cố định và lắp đặt cảm biến chắc chắn trên máy móc.',
                'image' => null,
                'order' => 0,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Gá cảm biến chính hãng, lắp đặt chắc chắn cho cảm biến - Bảng giá 2026',
                    'meta_description' => 'Gá cảm biến dùng cố định cảm biến quang, tiệm cận và các loại cảm biến khác. AutoSensor Việt Nam cung cấp phụ kiện phù hợp từng model, dễ lắp đặt và hỗ trợ tư vấn chọn đúng loại.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 35,
                'parent_id' => 8,
                'name' => 'Cáp cảm biến',
                'slug' => 'cap-cam-bien',
                'description' => 'Cáp cảm biến dùng kết nối tín hiệu giữa cảm biến và thiết bị điều khiển.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Cáp cảm biến chính hãng, kết nối ổn định cho hệ thống - Bảng giá 2026',
                    'meta_description' => 'Cáp cảm biến dùng kết nối tín hiệu cảm biến với PLC và bộ điều khiển. AutoSensor Việt Nam cung cấp cáp đúng chuẩn, độ bền cao, hỗ trợ tư vấn lựa chọn và cập nhật bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 36,
                'parent_id' => 8,
                'name' => 'Gương phản xạ cảm biến',
                'slug' => 'guong-phan-xa-cam-bien',
                'description' => 'Gương phản xạ dùng cho cảm biến quang phản xạ gương.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Gương phản xạ cảm biến chính hãng, phản xạ chính xác - Bảng giá 2026',
                    'meta_description' => 'Gương phản xạ dùng cho cảm biến quang phản xạ gương, giúp phát hiện vật thể chính xác hơn. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ tư vấn chọn đúng kích thước và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC APTOMAT
            // =========================

            [
                'id' => 37,
                'parent_id' => 9,
                'name' => 'MCB (Aptomat tép)',
                'slug' => 'mcb',
                'description' => 'MCB (Miniature Circuit Breaker) là aptomat tép dùng bảo vệ quá tải và ngắn mạch cho mạch điện nhánh.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'MCB – Aptomat tép bảo vệ mạch điện 1 pha, 3 pha - Bảng giá 2026',
                    'meta_description' => 'MCB dùng bảo vệ quá tải và ngắn mạch cho mạch điện nhánh, lắp DIN rail gọn gàng. AutoSensor Việt Nam cung cấp MCB chính hãng, thông số rõ ràng, hỗ trợ chọn dòng phù hợp và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 38,
                'parent_id' => 9,
                'name' => 'MCCB (Aptomat khối)',
                'slug' => 'mccb',
                'description' => 'MCCB (Molded Case Circuit Breaker) là aptomat khối dùng bảo vệ quá tải và ngắn mạch cho hệ thống điện.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'MCCB – Aptomat khối bảo vệ nguồn và tải điện, Bảng giá 2026',
                    'meta_description' => 'MCCB dùng bảo vệ quá tải và ngắn mạch, dòng cắt lớn, có thể chỉnh dòng. AutoSensor Việt Nam hỗ trợ tư vấn chọn MCCB đúng tải, đúng ứng dụng và cung cấp bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 39,
                'parent_id' => 9,
                'name' => 'ACB (Aptomat không khí)',
                'slug' => 'acb',
                'description' => 'ACB (Air Circuit Breaker) là aptomat không khí dùng cho tủ điện tổng và nguồn công suất lớn.',
                'image' => null,
                'order' => 3,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'ACB – Aptomat không khí cho tủ điện tổng và nguồn chính, Bảng giá 2026',
                    'meta_description' => 'ACB dùng cho tủ điện tổng, nguồn chính và hệ thống công suất lớn. AutoSensor Việt Nam cung cấp ACB chính hãng, hỗ trợ tư vấn cấu hình phù hợp và cập nhật bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 40,
                'parent_id' => 9,
                'name' => 'Aptomat chống giật (RCCB & RCBO)',
                'slug' => 'aptomat-chong-giat',
                'description' => 'Aptomat chống giật gồm RCCB và RCBO dùng phát hiện rò điện và bảo vệ an toàn.',
                'image' => null,
                'order' => 4,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Aptomat chống giật RCCB, RCBO bảo vệ an toàn điện - Bảng giá 2026',
                    'meta_description' => 'Aptomat chống giật RCCB và RCBO dùng phát hiện rò điện, bảo vệ người và thiết bị. AutoSensor Việt Nam hỗ trợ tư vấn chọn loại phù hợp và cung cấp sản phẩm chính hãng.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 41,
                'parent_id' => 9,
                'name' => 'Aptomat DC / Solar',
                'slug' => 'aptomat-dc-solar',
                'description' => 'Aptomat DC dùng bảo vệ hệ thống điện một chiều như pin, inverter và điện mặt trời.',
                'image' => null,
                'order' => 5,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Aptomat DC / Solar bảo vệ hệ thống điện một chiều, Bảng giá 2026',
                    'meta_description' => 'Aptomat DC dùng cho pin lưu trữ, inverter và hệ thống điện mặt trời. AutoSensor Việt Nam cung cấp aptomat DC chính hãng, hỗ trợ tư vấn chọn đúng dòng và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 43,
                'parent_id' => 9,
                'name' => 'MPCB (Aptomat bảo vệ động cơ)',
                'slug' => 'mpcb',
                'description' => 'MPCB (Motor Protection Circuit Breaker) là aptomat dùng bảo vệ động cơ điện.',
                'image' => null,
                'order' => 6,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'MPCB – Aptomat bảo vệ động cơ điện quá tải, ngắn mạch, Bảng giá 2026',
                    'meta_description' => 'MPCB dùng bảo vệ động cơ điện khỏi quá tải và ngắn mạch. AutoSensor Việt Nam hỗ trợ tư vấn chọn MPCB đúng công suất motor và cung cấp bảng giá cập nhật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC CÔNG TẮC, ĐÈN BÁO, CÒI BÁO
            // =========================

            [
                'id' => 44,
                'parent_id' => 12,
                'name' => 'Công tắc',
                'slug' => 'cong-tac',
                'description' => 'Công tắc dùng điều khiển đóng cắt mạch điện trong tủ điện và hệ thống điều khiển.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Công tắc chính hãng dùng cho tủ điện, điều khiển đóng cắt - Bảng giá 2026',
                    'meta_description' => 'Công tắc dùng điều khiển đóng cắt mạch điện trong tủ điện và hệ thống điều khiển. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, đa dạng chủng loại, hỗ trợ tư vấn chọn đúng loại và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 45,
                'parent_id' => 12,
                'name' => 'Đèn báo',
                'slug' => 'den-bao',
                'description' => 'Đèn báo dùng hiển thị trạng thái hoạt động của thiết bị và hệ thống.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Đèn báo chính hãng hiển thị trạng thái thiết bị trong tủ điện, Bảng giá 2026',
                    'meta_description' => 'Đèn báo dùng hiển thị trạng thái nguồn, lỗi và hoạt động của thiết bị. AutoSensor Việt Nam cung cấp đèn báo chính hãng, dễ lắp đặt, hỗ trợ tư vấn lựa chọn và cập nhật bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 46,
                'parent_id' => 12,
                'name' => 'Còi báo',
                'slug' => 'coi-bao',
                'description' => 'Còi báo dùng phát tín hiệu âm thanh cảnh báo trong hệ thống điện và điều khiển.',
                'image' => null,
                'order' => 3,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Còi báo chính hãng phát tín hiệu cảnh báo an toàn - Bảng giá 2026',
                    'meta_description' => 'Còi báo dùng phát tín hiệu âm thanh cảnh báo trong tủ điện và hệ thống điều khiển. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ tư vấn chọn loại phù hợp và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =========================
            // CẤP 2 – DANH MỤC BỘ ĐIỀU KHIỂN
            // =========================

            [
                'id' => 47,
                'parent_id' => 42,
                'name' => 'Bộ điều khiển nhiệt độ',
                'slug' => 'bo-dieu-khien-nhiet-do',
                'description' => 'Bộ điều khiển nhiệt độ dùng điều khiển và duy trì nhiệt độ ổn định cho thiết bị.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển nhiệt độ PID cho lò nhiệt, tủ sấy - Bảng giá 2026',
                    'meta_description' => 'Bộ điều khiển nhiệt độ hỗ trợ PID, ON/OFF, relay và SSR, dùng cho lò nhiệt và tủ sấy. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, tư vấn chọn đúng model và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 48,
                'parent_id' => 42,
                'name' => 'Bộ điều khiển mức',
                'slug' => 'bo-dieu-khien-muc',
                'description' => 'Bộ điều khiển mức dùng giám sát và điều khiển mức chất lỏng trong bồn bể.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển mức nước, chất lỏng cho bồn bể - Bảng giá 2026',
                    'meta_description' => 'Bộ điều khiển mức dùng giám sát và điều khiển mức nước, hóa chất trong bồn bể. AutoSensor Việt Nam hỗ trợ tư vấn chọn loại phù hợp ứng dụng và cung cấp bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 49,
                'parent_id' => 42,
                'name' => 'Bộ điều khiển áp suất',
                'slug' => 'bo-dieu-khien-ap-suat',
                'description' => 'Bộ điều khiển áp suất dùng giám sát và điều khiển áp suất khí nén, thủy lực.',
                'image' => null,
                'order' => 3,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển áp suất khí nén, thủy lực chính xác - Bảng giá 2026',
                    'meta_description' => 'Bộ điều khiển áp suất dùng giám sát và điều khiển áp suất ổn định. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ tư vấn chọn dải áp phù hợp và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 50,
                'parent_id' => 42,
                'name' => 'Bộ điều khiển động cơ',
                'slug' => 'bo-dieu-khien-dong-co',
                'description' => 'Bộ điều khiển động cơ dùng điều khiển khởi động, dừng và bảo vệ motor.',
                'image' => null,
                'order' => 4,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển động cơ điện khởi động và bảo vệ - Bảng giá 2026',
                    'meta_description' => 'Bộ điều khiển động cơ dùng khởi động, dừng và bảo vệ motor điện. AutoSensor Việt Nam hỗ trợ tư vấn chọn giải pháp phù hợp và cung cấp bảng giá cập nhật.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 51,
                'parent_id' => 42,
                'name' => 'Bộ điều khiển PID',
                'slug' => 'bo-dieu-khien-pid',
                'description' => 'Bộ điều khiển PID dùng điều khiển chính xác các đại lượng đo.',
                'image' => null,
                'order' => 5,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển PID chính xác cho nhiệt độ, áp suất - Bảng giá 2026',
                    'meta_description' => 'Bộ điều khiển PID dùng điều khiển nhiệt độ, áp suất và lưu lượng ổn định. AutoSensor Việt Nam cung cấp sản phẩm chính hãng, hỗ trợ tư vấn cấu hình và báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 52,
                'parent_id' => 42,
                'name' => 'Bộ điều khiển thời gian (Timer)',
                'slug' => 'bo-dieu-khien-thoi-gian',
                'description' => 'Bộ điều khiển thời gian dùng tạo trễ đóng cắt trong hệ thống điều khiển.',
                'image' => null,
                'order' => 6,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển thời gian Timer tạo trễ đóng cắt - Bảng giá 2026',
                    'meta_description' => 'Bộ điều khiển thời gian Timer dùng tạo trễ đóng cắt chính xác. AutoSensor Việt Nam cung cấp nhiều dải thời gian, dễ cài đặt và hỗ trợ báo giá nhanh.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => 53,
                'parent_id' => 42,
                'name' => 'Bộ điều khiển dòng điện',
                'slug' => 'bo-dieu-khien-dong-dien',
                'description' => 'Bộ điều khiển dòng điện dùng giám sát và điều khiển dòng AC/DC.',
                'image' => null,
                'order' => 7,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Bộ điều khiển dòng điện AC DC giám sát tải - Bảng giá 2026',
                    'meta_description' => 'Bộ điều khiển dòng điện dùng giám sát dòng AC/DC, bảo vệ tải và thiết bị. AutoSensor Việt Nam hỗ trợ tư vấn chọn đúng loại và cung cấp bảng giá mới.'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

        ], ['id']);
    }
}
