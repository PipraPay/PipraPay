<?php
    class BanglaQrGateway
    {
        public function info()
        {
            return [
                'title'       => 'Bangla QR',
                'logo'        => 'assets/logo.jpg',
                'currency'        => 'BDT',
                'tab'        => 'mfs',

                'gateway_type'        => 'automation',
                'sender_key'        => 'BanglaQR',
                'sender_type'        => 'Merchant',
            ];
        }

        public function color()
        {
            return [
                'primary_color'        => '#0F8A3B',
                'text_color'        => '#1F2937',
                'btn_color'        => '#E31E24',
                'btn_text_color'        => '#FFFFFF',
            ];
        }

        public function fields()
        {
            return [
                [
                    'name'  => 'qr_code',
                    'label' => 'Qr Code',
                    'type'  => 'image',
                ]
            ];
        }

        public function supported_languages()
        {
            return [
                'en' => 'English',
                'bn' => 'বাংলা',
            ];
        }

        public function lang_text()
        {
            return [
                '1' => [
                    'en' => 'Go to your any Mobile Banking App.',
                    'bn' => 'আপনার যেকোনো মোবাইল ব্যাংকিং অ্যাপে যান।',
                ],

                '2' => [
                    'en' => 'Click on "Scan QR" button to make payment.',
                    'bn' => 'পেমেন্ট করার জন্য “Scan QR” বাটনে ক্লিক করুন।',
                ],

                '3' => [
                    'en' => 'Scan the QR Code',
                    'bn' => 'কিউআর কোড স্ক্যান করুন',
                ],

                '4' => [
                    'en' => 'Or Enter MID: {mobile_number}',
                    'bn' => 'অথবা এমআইডি লিখুন: {mobile_number}',
                ],

                '5' => [
                    'en' => 'Enter the Amount: {amount} {currency}',
                    'bn' => 'পরিমাণ লিখুন: {amount} {currency}',
                ],

                '6' => [
                    'en' => 'Now enter your PIN to confirm.',
                    'bn' => 'এখন নিশ্চিত করতে আপনার পিন লিখুন।',
                ],

                '7' => [
                    'en' => 'Put the Transaction ID in the box below and press Verify',
                    'bn' => 'ট্রানজ্যাকশন আইডি নিচের বক্সে লিখুন এবং যাচাই করুন চাপুন।',
                ],
            ];
        }

        public function instructions($data)
        {
            return [
                [
                    'icon' => '',
                    'text' => '1',
                    'copy' => false,
                ],
                [
                    'icon' => '',
                    'text' => '2',
                    'copy' => false
                ],
                [
                    'icon' => '',
                    'text' => '3',
                    'action' => [
                        'type'  => 'image',
                        'label' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-qrcode"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M7 17l0 .01" /><path d="M14 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M7 7l0 .01" /><path d="M4 15a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M17 7l0 .01" /><path d="M14 14l3 0" /><path d="M20 14l0 .01" /><path d="M14 14l0 3" /><path d="M14 20l3 0" /><path d="M17 17l3 0" /><path d="M20 17l0 3" /></svg>',
                        'value' => $data['options']['qr_code'] ?? '',
                    ]
                ],
                [
                    'icon' => '',
                    'text' => '4',
                    'copy' => true,
                    'value' => $data['options']['mobile_number'] ?? '',
                    'vars' => [
                        '{mobile_number}' => $data['options']['mobile_number'] ?? ''
                    ]
                ],
                [
                    'icon' => '',
                    'text' => '5',
                    'copy' => true,
                    'value' => $data['transaction']['local_net_amount'],
                    'vars' => [
                        '{amount}' => $data['transaction']['local_net_amount'],
                        '{currency}' => $data['transaction']['local_currency']
                    ]
                ],
                [
                    'icon' => '',
                    'text' => '6',
                    'copy' => false
                ],
                [
                    'icon' => '',
                    'text' => '7',
                    'copy' => false
                ],


            ];
        }
    }
