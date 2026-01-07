<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BrandsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('brands')->upsert([
            [
                'id' => 1,
                'name' => 'Omron',
                'slug' => 'omron',
                'description' => 'Omron là thương hiệu hàng đầu về cảm biến, PLC, HMI và thiết bị tự động hóa công nghiệp từ Nhật Bản.',
                'image' => null,
                'order' => 0,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Omron chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Omron chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến quang, tiệm cận, PLC, HMI, rơ le công nghiệp với chất lượng cao, bảo hành đầy đủ và hỗ trợ kỹ thuật chuyên sâu. Liên hệ ngay để nhận báo giá tốt nhất và xem bảng giá 2026 cập nhật.',
                    'meta_keywords' => 'Omron, cảm biến Omron, PLC Omron, HMI Omron, thiết bị tự động hóa Omron'
                ]),
                'website' => 'https://www.omron.com',
                'country' => 'Nhật Bản',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Siemens',
                'slug' => 'siemens',
                'description' => 'Siemens là thương hiệu Đức nổi tiếng về giải pháp tự động hóa công nghiệp, PLC, HMI và biến tần.',
                'image' => null,
                'order' => 1,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Siemens chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Siemens chính hãng tại Việt Nam. Chúng tôi cung cấp PLC S7, HMI, biến tần, cảm biến công nghiệp với giải pháp tự động hóa toàn diện, chất lượng cao và bảo hành chính hãng. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi tốt nhất.',
                    'meta_keywords' => 'Siemens, PLC Siemens, HMI Siemens, biến tần Siemens, tự động hóa Siemens'
                ]),
                'website' => 'https://www.siemens.com',
                'country' => 'Đức',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Schneider',
                'slug' => 'schneider',
                'description' => 'Schneider Electric cung cấp giải pháp tự động hóa, aptomat, contactor và thiết bị điện công nghiệp.',
                'image' => null,
                'order' => 2,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Schneider Electric chính hãng AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Schneider Electric chính hãng tại Việt Nam. Chúng tôi cung cấp aptomat, contactor, PLC, HMI, biến tần với giải pháp điện và tự động hóa công nghiệp chất lượng cao, giá tốt. Liên hệ ngay để nhận báo giá và xem bảng giá 2026 cập nhật.',
                    'meta_keywords' => 'Schneider Electric, aptomat Schneider, contactor Schneider, PLC Schneider'
                ]),
                'website' => 'https://www.schneider-electric.com',
                'country' => 'Pháp',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'ABB',
                'slug' => 'abb',
                'description' => 'ABB là thương hiệu Thụy Sĩ chuyên về biến tần, motor, aptomat và thiết bị tự động hóa công nghiệp.',
                'image' => null,
                'order' => 3,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'ABB chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối ABB chính hãng tại Việt Nam. Chúng tôi cung cấp biến tần, motor, aptomat, contactor, PLC với giải pháp tự động hóa và điều khiển công nghiệp hiện đại, độ bền cao. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi đặc biệt.',
                    'meta_keywords' => 'ABB, biến tần ABB, motor ABB, aptomat ABB, thiết bị ABB'
                ]),
                'website' => 'https://www.abb.com',
                'country' => 'Thụy Sĩ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Mitsubishi',
                'slug' => 'mitsubishi',
                'description' => 'Mitsubishi Electric cung cấp PLC, HMI, biến tần, servo và giải pháp tự động hóa công nghiệp từ Nhật Bản.',
                'image' => null,
                'order' => 4,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Mitsubishi Electric chính hãng AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Mitsubishi Electric chính hãng tại Việt Nam. Chúng tôi cung cấp PLC FX, HMI, biến tần, servo, encoder với giải pháp tự động hóa công nghiệp chất lượng cao từ Nhật Bản, bảo hành đầy đủ. Liên hệ ngay để nhận báo giá tốt nhất và xem bảng giá 2026.',
                    'meta_keywords' => 'Mitsubishi Electric, PLC Mitsubishi, HMI Mitsubishi, biến tần Mitsubishi'
                ]),
                'website' => 'https://www.mitsubishielectric.com',
                'country' => 'Nhật Bản',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'Delta',
                'slug' => 'delta',
                'description' => 'Delta là thương hiệu Đài Loan chuyên về biến tần, servo, PLC và thiết bị tự động hóa công nghiệp.',
                'image' => null,
                'order' => 5,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Delta chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Delta chính hãng tại Việt Nam. Chúng tôi cung cấp biến tần, servo, PLC, HMI, encoder với giải pháp tự động hóa công nghiệp giá tốt, chất lượng cao và dễ tích hợp. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi tốt nhất từ đại lý chính thức.',
                    'meta_keywords' => 'Delta, biến tần Delta, servo Delta, PLC Delta, thiết bị Delta'
                ]),
                'website' => 'https://www.deltaww.com',
                'country' => 'Đài Loan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 7,
                'name' => 'Panasonic',
                'slug' => 'panasonic',
                'description' => 'Panasonic cung cấp cảm biến, rơ le, contactor và thiết bị tự động hóa công nghiệp chất lượng cao.',
                'image' => null,
                'order' => 6,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Panasonic chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Panasonic chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến, rơ le, contactor, timer với sản phẩm chất lượng cao, độ bền tốt và phù hợp môi trường công nghiệp. Liên hệ ngay để nhận báo giá và xem bảng giá 2026 cập nhật từ đại lý chính thức.',
                    'meta_keywords' => 'Panasonic, cảm biến Panasonic, rơ le Panasonic, contactor Panasonic'
                ]),
                'website' => 'https://www.panasonic.com',
                'country' => 'Nhật Bản',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 8,
                'name' => 'Keyence',
                'slug' => 'keyence',
                'description' => 'Keyence là thương hiệu Nhật Bản chuyên về cảm biến quang, vision sensor và thiết bị đo lường công nghiệp.',
                'image' => null,
                'order' => 7,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Keyence chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Keyence chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến quang, vision sensor, cảm biến laser với độ chính xác cao, ứng dụng trong kiểm tra và đo lường công nghiệp. Xem bảng giá 2026 và đặt hàng ngay để nhận hỗ trợ kỹ thuật chuyên sâu từ đại lý chính thức.',
                    'meta_keywords' => 'Keyence, cảm biến quang Keyence, vision sensor Keyence, thiết bị Keyence'
                ]),
                'website' => 'https://www.keyence.com',
                'country' => 'Nhật Bản',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 9,
                'name' => 'Sick',
                'slug' => 'sick',
                'description' => 'Sick là thương hiệu Đức chuyên về cảm biến công nghiệp, safety sensor và giải pháp tự động hóa.',
                'image' => null,
                'order' => 8,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Sick chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Sick chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến quang, tiệm cận, safety sensor, encoder với giải pháp an toàn và tự động hóa công nghiệp từ Đức, chất lượng cao. Liên hệ ngay để nhận báo giá tốt nhất và xem bảng giá 2026 từ đại lý chính thức.',
                    'meta_keywords' => 'Sick, cảm biến Sick, safety sensor Sick, encoder Sick'
                ]),
                'website' => 'https://www.sick.com',
                'country' => 'Đức',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 10,
                'name' => 'IFM',
                'slug' => 'ifm',
                'description' => 'IFM là thương hiệu Đức chuyên về cảm biến công nghiệp, hệ thống điều khiển và giải pháp tự động hóa.',
                'image' => null,
                'order' => 9,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'IFM chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối IFM chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến áp suất, nhiệt độ, mức, lưu lượng với hệ thống điều khiển và giải pháp tự động hóa công nghiệp chất lượng cao từ Đức. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi đặc biệt.',
                    'meta_keywords' => 'IFM, cảm biến IFM, hệ thống điều khiển IFM, thiết bị IFM'
                ]),
                'website' => 'https://www.ifm.com',
                'country' => 'Đức',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 11,
                'name' => 'Autonics',
                'slug' => 'autonics',
                'description' => 'Autonics là thương hiệu Hàn Quốc chuyên về cảm biến, bộ điều khiển và thiết bị tự động hóa công nghiệp.',
                'image' => null,
                'order' => 10,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Autonics chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Autonics chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến quang, tiệm cận, bộ điều khiển nhiệt độ, timer với sản phẩm chất lượng, giá tốt và phù hợp ứng dụng công nghiệp. Liên hệ ngay để nhận báo giá và xem bảng giá 2026 cập nhật từ đại lý chính thức.',
                    'meta_keywords' => 'Autonics, cảm biến Autonics, bộ điều khiển Autonics, timer Autonics'
                ]),
                'website' => 'https://www.autonics.com',
                'country' => 'Hàn Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 12,
                'name' => 'LS',
                'slug' => 'ls',
                'description' => 'LS Electric cung cấp PLC, HMI, biến tần, contactor và thiết bị tự động hóa công nghiệp từ Hàn Quốc.',
                'image' => null,
                'order' => 11,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'LS Electric chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối LS Electric chính hãng tại Việt Nam. Chúng tôi cung cấp PLC, HMI, biến tần, contactor, aptomat với giải pháp tự động hóa công nghiệp giá tốt, chất lượng cao và dễ tích hợp. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi tốt nhất từ đại lý chính thức.',
                    'meta_keywords' => 'LS Electric, PLC LS, HMI LS, biến tần LS, contactor LS'
                ]),
                'website' => 'https://www.ls-electric.com',
                'country' => 'Hàn Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 13,
                'name' => 'Fuji',
                'slug' => 'fuji',
                'description' => 'Fuji Electric là thương hiệu Nhật Bản chuyên về biến tần, contactor, aptomat và thiết bị tự động hóa.',
                'image' => null,
                'order' => 12,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Fuji Electric chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Fuji Electric chính hãng tại Việt Nam. Chúng tôi cung cấp biến tần, contactor, aptomat, rơ le với sản phẩm chất lượng cao, độ bền tốt và phù hợp môi trường công nghiệp. Liên hệ ngay để nhận báo giá và xem bảng giá 2026 cập nhật từ đại lý chính thức.',
                    'meta_keywords' => 'Fuji Electric, biến tần Fuji, contactor Fuji, aptomat Fuji'
                ]),
                'website' => 'https://www.fujielectric.com',
                'country' => 'Nhật Bản',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 14,
                'name' => 'Honeywell',
                'slug' => 'honeywell',
                'description' => 'Honeywell cung cấp cảm biến, bộ điều khiển và giải pháp tự động hóa công nghiệp từ Mỹ.',
                'image' => null,
                'order' => 13,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Honeywell chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Honeywell chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến áp suất, nhiệt độ, bộ điều khiển PID với giải pháp tự động hóa và đo lường công nghiệp chất lượng cao từ Mỹ. Xem bảng giá 2026 và đặt hàng ngay để nhận hỗ trợ kỹ thuật chuyên sâu.',
                    'meta_keywords' => 'Honeywell, cảm biến Honeywell, bộ điều khiển Honeywell, thiết bị Honeywell'
                ]),
                'website' => 'https://www.honeywell.com',
                'country' => 'Mỹ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 15,
                'name' => 'Hanyoung',
                'slug' => 'hanyoung',
                'description' => 'Hanyoung là thương hiệu Hàn Quốc chuyên về bộ điều khiển nhiệt độ, timer và thiết bị đo lường.',
                'image' => null,
                'order' => 14,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Hanyoung chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Hanyoung chính hãng tại Việt Nam. Chúng tôi cung cấp bộ điều khiển nhiệt độ, timer, bộ hiển thị với độ ổn định cao, dễ cài đặt và phù hợp nhiều ứng dụng điều khiển. Liên hệ ngay để nhận báo giá tốt nhất và xem bảng giá 2026 từ đại lý chính thức.',
                    'meta_keywords' => 'Hanyoung, bộ điều khiển Hanyoung, timer Hanyoung, điều khiển nhiệt độ Hanyoung'
                ]),
                'website' => 'https://www.hanyoungnux.com',
                'country' => 'Hàn Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 16,
                'name' => 'Proface',
                'slug' => 'proface',
                'description' => 'Proface là thương hiệu HMI nổi tiếng của Nhật Bản, chuyên màn hình giao diện người máy.',
                'image' => null,
                'order' => 15,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Proface chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Proface chính hãng tại Việt Nam. Chúng tôi cung cấp màn hình HMI với giao diện trực quan, độ bền cao, dễ lập trình và phù hợp tủ điều khiển và dây chuyền tự động. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi đặc biệt từ đại lý chính thức.',
                    'meta_keywords' => 'Proface, HMI Proface, màn hình HMI Proface'
                ]),
                'website' => 'https://www.proface.com',
                'country' => 'Nhật Bản',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 17,
                'name' => 'Woonyoung',
                'slug' => 'woonyoung',
                'description' => 'Woonyoung là thương hiệu Hàn Quốc chuyên thiết bị an toàn và cảm biến công nghiệp.',
                'image' => null,
                'order' => 16,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Woonyoung chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Woonyoung chính hãng tại Việt Nam. Chúng tôi cung cấp cảm biến, rơ le an toàn và giải pháp bảo vệ máy móc với độ tin cậy cao, được sử dụng rộng rãi trong hệ thống tự động. Liên hệ ngay để nhận báo giá và xem bảng giá 2026 cập nhật từ đại lý chính thức.',
                    'meta_keywords' => 'Woonyoung, cảm biến Woonyoung, rơ le an toàn Woonyoung'
                ]),
                'website' => 'https://www.wyn.co.kr',
                'country' => 'Hàn Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 18,
                'name' => 'Myungbo',
                'slug' => 'myungbo',
                'description' => 'Myungbo là thương hiệu Hàn Quốc chuyên bộ đếm, bộ hiển thị và thiết bị đo.',
                'image' => null,
                'order' => 17,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Myungbo chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Myungbo chính hãng tại Việt Nam. Chúng tôi cung cấp bộ đếm, bộ hiển thị và thiết bị đo với hoạt động ổn định, dễ lắp đặt và phù hợp nhiều hệ thống điều khiển. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi tốt nhất từ đại lý chính thức.',
                    'meta_keywords' => 'Myungbo, bộ đếm Myungbo, bộ hiển thị Myungbo'
                ]),
                'website' => 'https://www.myungbo.co.kr',
                'country' => 'Hàn Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 19,
                'name' => 'MeanWell',
                'slug' => 'meanwell',
                'description' => 'Mean Well là thương hiệu Đài Loan nổi tiếng về bộ nguồn công nghiệp chất lượng cao.',
                'image' => null,
                'order' => 18,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Mean Well chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Mean Well chính hãng tại Việt Nam. Chúng tôi cung cấp bộ nguồn công nghiệp dùng cho tủ điện, PLC, HMI và thiết bị điều khiển với độ ổn định cao, đa dạng công suất và giá tốt. Liên hệ ngay để nhận báo giá và xem bảng giá 2026 cập nhật từ đại lý chính thức.',
                    'meta_keywords' => 'Mean Well, bộ nguồn Mean Well, nguồn Mean Well'
                ]),
                'website' => 'https://www.meanwell.com',
                'country' => 'Đài Loan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 20,
                'name' => 'Molex',
                'slug' => 'molex',
                'description' => 'Molex là thương hiệu toàn cầu chuyên đầu nối, cáp và giải pháp kết nối điện tử.',
                'image' => null,
                'order' => 19,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Molex chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Molex chính hãng tại Việt Nam. Chúng tôi cung cấp đầu nối, cáp và giải pháp kết nối điện tử với độ bền cao, tiêu chuẩn quốc tế và phù hợp nhiều ứng dụng công nghiệp. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi đặc biệt từ đại lý chính thức.',
                    'meta_keywords' => 'Molex, đầu nối Molex, cáp Molex'
                ]),
                'website' => 'https://www.molex.com',
                'country' => 'Hoa Kỳ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 21,
                'name' => 'Kacon',
                'slug' => 'kacon',
                'description' => 'Kacon là thương hiệu Hàn Quốc chuyên rơ le, timer và thiết bị điều khiển.',
                'image' => null,
                'order' => 20,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Kacon chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Kacon chính hãng tại Việt Nam. Chúng tôi cung cấp rơ le, timer và bộ điều khiển với hoạt động ổn định, dễ thay thế trong tủ điện và giá tốt. Liên hệ ngay để nhận báo giá tốt nhất và xem bảng giá 2026 từ đại lý chính thức.',
                    'meta_keywords' => 'Kacon, rơ le Kacon, timer Kacon'
                ]),
                'website' => 'https://www.kacon.co.kr',
                'country' => 'Hàn Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 22,
                'name' => 'Chint',
                'slug' => 'chint',
                'description' => 'Chint là thương hiệu thiết bị điện nổi tiếng với aptomat, contactor và thiết bị đóng cắt.',
                'image' => null,
                'order' => 21,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Chint chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Chint chính hãng tại Việt Nam. Chúng tôi cung cấp aptomat, contactor và thiết bị điện hạ thế với giải pháp kinh tế, dễ lắp đặt và phù hợp nhiều ứng dụng công nghiệp. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi tốt nhất từ đại lý chính thức.',
                    'meta_keywords' => 'Chint, aptomat Chint, thiết bị điện Chint'
                ]),
                'website' => 'https://www.chint.com',
                'country' => 'Trung Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 23,
                'name' => 'Boxco',
                'slug' => 'boxco',
                'description' => 'Boxco là thương hiệu chuyên vỏ tủ điện, hộp nhựa và hộp điều khiển.',
                'image' => null,
                'order' => 22,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Boxco chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Boxco chính hãng tại Việt Nam. Chúng tôi cung cấp vỏ tủ điện, hộp nhựa và hộp điều khiển với độ bền cao, chống bụi và ẩm, phù hợp lắp đặt trong nhiều môi trường. Liên hệ ngay để nhận báo giá và xem bảng giá 2026 cập nhật từ đại lý chính thức.',
                    'meta_keywords' => 'Boxco, vỏ tủ Boxco, hộp điện Boxco'
                ]),
                'website' => 'https://www.boxco.com',
                'country' => 'Hàn Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 24,
                'name' => 'INVT',
                'slug' => 'invt',
                'description' => 'INVT là thương hiệu chuyên biến tần và giải pháp điều khiển động cơ.',
                'image' => null,
                'order' => 23,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'INVT chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối INVT chính hãng tại Việt Nam. Chúng tôi cung cấp biến tần và giải pháp điều khiển động cơ cho máy móc và dây chuyền với giá hợp lý, dễ cài đặt và hỗ trợ kỹ thuật chuyên nghiệp. Xem bảng giá 2026 và đặt hàng ngay để nhận ưu đãi đặc biệt.',
                    'meta_keywords' => 'INVT, biến tần INVT, inverter INVT'
                ]),
                'website' => 'https://www.invt.com',
                'country' => 'Trung Quốc',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 25,
                'name' => 'Weintek',
                'slug' => 'weintek',
                'description' => 'Weintek là thương hiệu HMI phổ biến với giao diện trực quan và giá hợp lý.',
                'image' => null,
                'order' => 24,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Weintek chính hãng tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối Weintek chính hãng tại Việt Nam. Chúng tôi cung cấp màn hình HMI với dễ lập trình, hỗ trợ nhiều giao thức PLC và phù hợp nhiều hệ thống điều khiển, giá tốt. Liên hệ ngay để nhận báo giá tốt nhất và xem bảng giá 2026 từ đại lý chính thức.',
                    'meta_keywords' => 'Weintek, HMI Weintek, màn hình Weintek'
                ]),
                'website' => 'https://www.weintek.com',
                'country' => 'Đài Loan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 26,
                'name' => 'Hãng khác',
                'slug' => 'hang-khac',
                'description' => 'Các thương hiệu khác trong lĩnh vực thiết bị tự động hóa công nghiệp.',
                'image' => null,
                'order' => 99,
                'is_active' => 1,
                'metadata' => json_encode([
                    'meta_title' => 'Thương hiệu khác tại AutoSensor Việt Nam - Đại lý phân phối | Bảng giá 2026',
                    'meta_description' => 'AutoSensor Việt Nam là đại lý phân phối các thương hiệu khác trong lĩnh vực thiết bị tự động hóa công nghiệp tại Việt Nam. Chúng tôi cung cấp đa dạng thương hiệu, đáp ứng mọi nhu cầu với sản phẩm chính hãng, giá tốt và hỗ trợ kỹ thuật chuyên nghiệp. Xem bảng giá 2026 và đặt hàng ngay.',
                    'meta_keywords' => 'thương hiệu khác, thiết bị tự động hóa, giải pháp công nghiệp'
                ]),
                'website' => null,
                'country' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
            
        ], ['id']);
    }
}
