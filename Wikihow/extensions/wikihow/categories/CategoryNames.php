<?php

require_once("$IP/extensions/wikihow/Misc.php");

$englishCategs = [
	CAT_ARTS => "Arts and Entertainment",
	CAT_CARS => "Cars & Other Vehicles",
	CAT_COMPUTERS => "Computers and Electronics",
	CAT_EDUCATION => "Education and Communications",
	CAT_FAMILY => "Family Life",
	CAT_FINANCE => "Finance and Business",
	CAT_FOOD => "Food and Entertaining",
	CAT_HEALTH => "Health",
	CAT_HOBBIES => "Hobbies and Crafts",
	CAT_HOLIDAYS => "Holidays and Traditions",
	CAT_HOME => "Home and Garden",
	CAT_PERSONAL => "Personal Care and Style",
	CAT_PETS => "Pets and Animals",
	CAT_PHILOSOPHY => "Philosophy and Religion",
	CAT_RELATIONSHIPS => "Relationships",
	CAT_SPORTS => "Sports and Fitness",
	CAT_TRAVEL => "Travel",
	CAT_WIKIHOW => "WikiHow",
	CAT_WORK => "Work World",
	CAT_YOUTH => "Youth",
];

if ($wgLanguageCode == 'en' ) {
	$wgCategoryNames = $englishCategs;
} else {
	$wgCategoryNamesEn = $englishCategs;
}

if ($wgLanguageCode == 'es') {
	$wgCategoryNames = array(
			CAT_ARTS => "Arte y entretenimiento",
			CAT_CARS => "Automóviles y otros vehículos",
			CAT_COMPUTERS => "Computadoras y electrónica",
			CAT_EDUCATION => "Carreras y educación",
			CAT_FAMILY => "Vida familiar",
			CAT_FINANCE => "Finanzas y negocios",
			CAT_FOOD => "Comida y diversión",
			CAT_HEALTH => "Salud",
			CAT_HOBBIES => "Pasatiempos",
			CAT_HOME => "En la casa y el jardín",
			CAT_HOLIDAYS => "Días de fiesta y tradiciones",
			CAT_PERSONAL => "Cuidado y estilo personal",
			CAT_PETS => "Mascotas",
			CAT_PHILOSOPHY => "Filosofía y religión",
			CAT_RELATIONSHIPS => "Relaciones sociales",
			CAT_SPORTS => "Deportes",
			CAT_TRAVEL => "Viajes",
			// CAT_WIKIHOW => "wikiHow",
			CAT_WORK => "En el trabajo",
			CAT_YOUTH => "Adolescentes",
			);
}
elseif($wgLanguageCode == 'pt') {
	$wgCategoryNames = array(
			CAT_ARTS => "Arte e Entretenimento",
			CAT_CARS => "Automóveis e Outros Veículos",
			CAT_COMPUTERS => "Informática e Eletrônica",
			CAT_EDUCATION => "Educação e Comunicação",
			CAT_FAMILY => "Vida em Família",
			CAT_FINANCE => "Negócios e Finanças",
			CAT_FOOD => "Culinária e Gastronomia",
			CAT_HEALTH => "Saúde",
			CAT_HOBBIES => "Artesanato e Hobby",
			CAT_HOME => "Casa e Jardim",
			CAT_HOLIDAYS => "Festas e Tradições",
			CAT_PERSONAL => "Cuidados Pessoais e Estilo",
			CAT_PETS => "Animais",
			CAT_PHILOSOPHY => "Filosofia e Religião",
			CAT_RELATIONSHIPS => "Relacionamento",
			CAT_SPORTS => "Esportes",
			CAT_TRAVEL => "Viagens",
			// CAT_WIKIHOW => "wikiHow",
			CAT_WORK => "Carreira e Profissão",
			CAT_YOUTH => "Juventude",
			);

}
elseif($wgLanguageCode == 'de') {
	$wgCategoryNames = array(
			CAT_ARTS => "Kunst & Unterhaltung",
			CAT_CARS => "Autos & andere Fahrzeuge",
			CAT_COMPUTERS => "Technik & Elektronik",
			CAT_EDUCATION => "Kommunikation",
			CAT_FAMILY => "Familienleben",
			CAT_FINANCE => "Finanzen & Geschäfte",
			CAT_FOOD => "Essen & Ausgehen",
			CAT_HEALTH => "Gesundheit",
			CAT_HOBBIES => "Hobbys & Basteln",
			CAT_HOME => "Haus & Garten",
			CAT_HOLIDAYS => "Feste & Traditionen",
			CAT_PERSONAL => "Pflege & Schönheit",
			CAT_PETS => "Tiere",
			CAT_PHILOSOPHY => "Philosophie & Religion",
			CAT_RELATIONSHIPS => "Beziehungen",
			CAT_SPORTS => "Sport & Fitness",
			CAT_TRAVEL => "Urlaub & Reisen",
			// CAT_WIKIHOW => "wikiHow",
			CAT_WORK => "Beruf & Bildung",
			CAT_YOUTH => "Jugend"

				);

}
elseif($wgLanguageCode == 'fr') {
	$wgCategoryNames = array(
			CAT_ARTS => "Arts et loisirs",
			CAT_CARS => "Voitures et autres véhicules",
			CAT_COMPUTERS => "Ordinateurs et high tech",
			CAT_EDUCATION => "Communication et éducation",
			CAT_FAMILY => "Vie de la famille",
			CAT_FINANCE => "Finances et affaires",
			CAT_FOOD => "Cuisine et gastronomie",
			CAT_HEALTH => "Santé",
			CAT_HOBBIES => "Passetemps",
			CAT_HOME => "Maison et jardin",
			CAT_HOLIDAYS => "Fêtes et traditions",
			CAT_PERSONAL => "Soin et style de vie",
			CAT_PETS => "Animaux",
			CAT_PHILOSOPHY => "Philosophie et religion",
			CAT_RELATIONSHIPS => "Relations sociales",
			CAT_SPORTS => "Sports et mise en forme",
			CAT_TRAVEL => "Voyages",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "Monde du travail et de l'emploi",
			CAT_YOUTH => "Jeunesse",
			);

}
elseif($wgLanguageCode == 'it') {
	$wgCategoryNames = array(
			CAT_ARTS => "Arte & Intrattenimento",
			CAT_CARS => "Automobili & altri Veicoli",
			CAT_COMPUTERS => "Computer & Elettronica",
			CAT_EDUCATION => "Istruzione & Comunicazione",
			CAT_FAMILY => "Famiglia",
			CAT_FINANCE => "Finanza & Business",
			CAT_FOOD => "Cucina",
			CAT_HEALTH => "Salute",
			CAT_HOBBIES => "Hobby & Fai da Te",
			CAT_HOME => "Casa & Giardino",
			CAT_HOLIDAYS => "Festività & Tradizioni",
			CAT_PERSONAL => "Cura Personale & Stile",
			CAT_PETS => "Animali",
			CAT_PHILOSOPHY => "Filosofia & Religione",
			CAT_RELATIONSHIPS => "Relazioni Interpersonali",
			CAT_SPORTS => "Sport & Fitness",
			CAT_TRAVEL => "Viaggiare",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "Il Mondo del Lavoro",
			CAT_YOUTH => "Gioventù",
			);

}
elseif($wgLanguageCode == 'ru') {
	$wgCategoryNames = array(

			CAT_ARTS => "Искусство и развлечения",
			CAT_CARS => "Транспорт",
			CAT_COMPUTERS => "Компьютеры и электроника",
			CAT_EDUCATION => "Образование и коммуникации",
			CAT_FAMILY => "Семейная жизнь",
			CAT_FINANCE => "Финансы и бизнес",
			CAT_FOOD => "Кулинария и гостеприимство",
			CAT_HEALTH => "Здоровье",
			CAT_HOBBIES => "Хобби и рукоделие",
			CAT_HOME => "Дом и сад",
			CAT_HOLIDAYS => "Праздники и традиции",
			CAT_PERSONAL => "Стиль и уход за собой",
			CAT_PETS => "Питомцы и животные",
			CAT_PHILOSOPHY => "Филисофия и религия",
			CAT_RELATIONSHIPS => "Взаимоотношения",
			CAT_SPORTS => "Спорт и фитнес",
			CAT_TRAVEL => "Путешествия",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "Мир работы",
			CAT_YOUTH => "Молодежь",
			);
}
elseif($wgLanguageCode == 'zh') {
	$wgCategoryNames = array(

			CAT_ARTS => "艺术与娱乐",
			CAT_CARS => "汽车与其他交通工具",
			CAT_COMPUTERS => "计算机与电子产品",
			CAT_EDUCATION => "教育与交流",
			CAT_FAMILY => "家庭生活",
			CAT_FINANCE => "金融与商业",
			CAT_FOOD => "饮食与休闲",
			CAT_HEALTH => "健康",
			CAT_HOBBIES => "兴趣与手艺",
			CAT_HOME => "家居与园艺",
			CAT_HOLIDAYS => "假期与节日",
			CAT_PERSONAL => "个人形象与时尚",
			CAT_PETS => "宠物与动物",
			CAT_PHILOSOPHY => "宗教与哲学",
			CAT_RELATIONSHIPS => "人际关系",
			CAT_SPORTS => "运动与保健",
			CAT_TRAVEL => "旅行",
			// CAT_WIKIHOW => "wikiHow",
			CAT_WORK => "工作",
			CAT_YOUTH => "青少年",
			);
}
elseif($wgLanguageCode == 'nl') {
	$wgCategoryNames = array(

			CAT_ARTS => "Kunst & Amusement",
			CAT_CARS => "Auto's & Andere Voertuigen",
			CAT_COMPUTERS => "Computers & Elektronica",
			CAT_EDUCATION => "Onderwijs & Communicatie",
			CAT_FAMILY => "Gezinsleven",
			CAT_FINANCE => "Financieel & Recht",
			CAT_FOOD => "Eten & Uitgaan",
			CAT_HEALTH => "Gezondheid",
			CAT_HOBBIES => "Hobby's & Handwerk",
			CAT_HOME => "Huis & Tuin",
			CAT_HOLIDAYS => "Feestdagen & Tradities",
			CAT_PERSONAL => "Persoonlijke Verzorging",
			CAT_PETS => "Dieren",
			CAT_PHILOSOPHY => "Filosofie & Religie",
			CAT_RELATIONSHIPS => "Relaties",
			CAT_SPORTS => "Sport & Welzijn",
			CAT_TRAVEL => "Vakantie & Reizen",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "Werk & Carrière",
			CAT_YOUTH => "Jeugd",
			);
}
elseif($wgLanguageCode=='hi') {
	// TODO: Set categories for Hindi
	$wgCategoryNames = array(
			CAT_ARTS => "कला और मनोरंजन",
			CAT_CARS => "कारों और अन्य वाहनों",
			CAT_COMPUTERS => "कंप्यूटर और इलेक्ट्रॉनिक्स",
			CAT_EDUCATION => "शिक्षा और संचार",
			CAT_FAMILY => "पारिवारिक जीवन",
			CAT_FINANCE => "वित्त, व्यापार और कानूनी",
			CAT_FOOD => "खाद्य और मनोरंजक",
			CAT_HEALTH => "स्वास्थ्य",
			CAT_HOBBIES => "रूचियाँ और शिल्प",
			CAT_HOME => "घर और उद्यान",
			CAT_HOLIDAYS => "छुट्टियाँ और परंपरा",
			CAT_PERSONAL => "व्यक्तिगत देखभाल और शैली",
			CAT_PETS => "पालतू जानवर और पशु",
			CAT_PHILOSOPHY => "दर्शन और धर्म",
			CAT_RELATIONSHIPS => "रिश्ते",
			CAT_SPORTS => "खेल और दुरुस्ती",
			CAT_TRAVEL => "यात्रा",
			// CAT_WIKIHOW => "विकिहाउ",
			CAT_WORK => "कार्य की दुनिया",
			CAT_YOUTH => "युवा",
			);

}
elseif($wgLanguageCode == 'cs') {
	$wgCategoryNames = array(

			CAT_ARTS => "Umění a Zábava",
			CAT_CARS => "Auta a Ostatní Vozidla",
			CAT_COMPUTERS => "Počítače a Elektronika",
			CAT_EDUCATION => "Vzdělání a Komunikace",
			CAT_FAMILY => "Rodinný život",
			CAT_FINANCE => "Finance a Obchod",
			CAT_FOOD => "Jídlo",
			CAT_HEALTH => "Zdraví",
			CAT_HOBBIES => "Záliby a Řemesla",
			CAT_HOME => "Dům a Zahrada",
			CAT_HOLIDAYS => "Dovolená a Tradice",
			CAT_PERSONAL => "Osobní péče a Styl",
			CAT_PETS => "Mazlíčci a Zvířata",
			CAT_PHILOSOPHY => "Filozofie a Náboženství",
			CAT_RELATIONSHIPS => "Vztahy",
			CAT_SPORTS => "Sporty a Fitness",
			CAT_TRAVEL => "Cestování",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "Svět Práce",
			CAT_YOUTH => "Mládí",
			);
}
elseif($wgLanguageCode == 'id') {
	$wgCategoryNames = array(

			CAT_ARTS => "Seni & Hiburan",
			CAT_CARS => "Mobil & Otomotif",
			CAT_COMPUTERS => "Komputer & Elektronik",
			CAT_EDUCATION => "Pendidikan & Komunikasi",
			CAT_FAMILY => "Keluarga",
			CAT_FINANCE => "Keuangan & Bisnis",
			CAT_FOOD => "Makanan & Penjamuan",
			CAT_HEALTH => "Kesehatan",
			CAT_HOBBIES => "Hobi & Kerajinan Tangan",
			CAT_HOME => "Rumah & Taman",
			CAT_HOLIDAYS => "Hari Raya & Tradisi",
			CAT_PERSONAL => "Perawatan Diri & Gaya",
			CAT_PETS => "Peliharaan & Hewan",
			CAT_PHILOSOPHY => "Filsafat & Agama",
			CAT_RELATIONSHIPS => "Hubungan Pribadi",
			CAT_SPORTS => "Olahraga & Kebugaran",
			CAT_TRAVEL => 'Travel',
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "Dunia Kerja",
			CAT_YOUTH => "Kawula Muda",
			);
}
elseif($wgLanguageCode == 'ja') {
	$wgCategoryNames = array(
			CAT_ARTS => "アート・エンタメ",
			CAT_CARS => "車・乗り物",
			CAT_COMPUTERS => "パソコン・電子機器",
			CAT_EDUCATION => "学び・コミュニケーション",
			CAT_FAMILY => "家族",
			CAT_FINANCE => "ビジネス・ファイナンス",
			CAT_FOOD => "食・おもてなし",
			CAT_HEALTH => "健康",
			CAT_HOBBIES => "趣味・DIY",
			CAT_HOLIDAYS => "祝祭日・年中行事",
			CAT_HOME => "住まいと暮らし・ガーデニング",
			CAT_PERSONAL => "ビューティー・ファッション",
			CAT_PETS => "ペット・動物",
			CAT_PHILOSOPHY => "哲学・宗教",
			CAT_RELATIONSHIPS => "人間関係",
			CAT_SPORTS => "スポーツ・フィットネス",
			CAT_TRAVEL => "旅行",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "仕事",
			CAT_YOUTH => "ティーン",
			);

}
elseif($wgLanguageCode == 'th') {
	$wgCategoryNames = array(
			CAT_ARTS => "ศิลปะและความบันเทิง",
			CAT_CARS =>  "รถยนต์และยานพาหนะอื่น ๆ",
			CAT_COMPUTERS =>  "คอมพิวเตอร์และอิเล็กทรอนิกส์",
			CAT_EDUCATION =>  "การสื่อสารและการศึกษา",
			CAT_FAMILY => "ชีวิตครอบครัว",
			CAT_FINANCE => "การเงินและธุรกิจ",
			CAT_FOOD =>  "การทำอาหารและทำอาหาร",
			CAT_HEALTH => "สุขภาพ",
			CAT_HOBBIES =>  "งานอดิเรก",
			CAT_HOLIDAYS =>  "ฉลอง",
			CAT_HOME => "บ้านและสวน",
			CAT_PERSONAL =>  "การดูแลและปรับปรุงตนเอง",
			CAT_PETS =>  "สัตว์และสัตว์เลี้ยง",
			CAT_PHILOSOPHY => "ปรัชญาและศาสนา",
			CAT_RELATIONSHIPS =>  "ความสัมพันธ์",
			CAT_SPORTS => "กีฬาและการออกกำลังกาย",
			CAT_TRAVEL =>  "การท่องเที่ยว",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK =>  "โลกของการทำงานและการจ้างงาน",
			CAT_YOUTH =>  "เยาวชน"
			);

}
elseif($wgLanguageCode == 'ar') {
	$wgCategoryNames = array(
			CAT_ARTS => "الفنون والترفيه",
			CAT_CARS => "السيارات والمركبات الأخرى",
			CAT_COMPUTERS => "أجهزة الكمبيوتر والإلكترونيات",
			CAT_EDUCATION => "التعليم والتواصل",
			CAT_FAMILY => "الحياة الأسرية",
			CAT_FINANCE => "المال والأعمال",
			CAT_FOOD => "الغذاء والتسلية",
			CAT_HEALTH => "الصحة",
			CAT_HOBBIES => "الهوايات والحرف",
			CAT_HOLIDAYS => "الأعياد والتقاليد",
			CAT_HOME => "المنزل والحديقة",
			CAT_PERSONAL => "العناية الشخصية والموضة",
			CAT_PETS => "الحيوانات الأليفة والحيوانات",
			CAT_PHILOSOPHY => "الفلسفة والدين",
			CAT_RELATIONSHIPS => "العلاقات الشخصية",
			CAT_SPORTS => "الرياضة واللياقة البدنية",
			CAT_TRAVEL => "سفر",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "عالم العمل",
			CAT_YOUTH => "شباب",
			);
}
elseif($wgLanguageCode == 'vi') {
	$wgCategoryNames = array(
			CAT_ARTS => "Nghệ thuật và Giải trí",
			CAT_CARS => "Xe hơi và Phương tiện Khác",
			CAT_COMPUTERS => "Máy tính và Điện tử",
			CAT_EDUCATION => "Giáo dục và Truyền thông",
			CAT_FAMILY => "Cuộc sống Gia đình",
			CAT_FINANCE => "Tài chính và Kinh doanh",
			CAT_FOOD => "Ẩm thực và Giải trí",
			CAT_HEALTH => "Sức khỏe",
			CAT_HOBBIES => "Sở thích và Thủ công Mỹ nghệ",
			CAT_HOLIDAYS => "Ngày lễ và Truyền thống",
			CAT_HOME => "Nhà ở và Làm vườn",
			CAT_PERSONAL => "Chăm sóc Cơ thể",
			CAT_PETS => "Thú cưng và Động vật",
			CAT_PHILOSOPHY => "Triết học và Tôn giáo",
			CAT_RELATIONSHIPS => "Mối quan hệ",
			CAT_SPORTS => "Thể thao và Thẩm mỹ",
			CAT_TRAVEL => "Du lịch",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "Thế giới Làm việc",
			CAT_YOUTH => "Giới trẻ",
			);
}
elseif($wgLanguageCode == 'ko') {
	$wgCategoryNames = array(
			CAT_ARTS => "대중문화와 예술",
			CAT_CARS => "자동차 및 기계",
			CAT_COMPUTERS => "컴퓨터 및 전자기기",
			CAT_EDUCATION => "교육 및 커뮤니케이션",
			CAT_FAMILY => "가족 및 일상생활",
			CAT_FINANCE => "금융 및 비지니스",
			CAT_FOOD => "요리와 식생활",
			CAT_HEALTH => "건강",
			CAT_HOBBIES => "취미 및 공예",
			CAT_HOLIDAYS => "기념일",
			CAT_HOME => "주택 및 인테리어",
			CAT_PERSONAL => "퍼스널 케어 앤 스타일",
			CAT_PETS => "애완동물",
			CAT_PHILOSOPHY => "종교와 철학",
			CAT_RELATIONSHIPS => "연애",
			CAT_SPORTS => "스포츠 및 피트니스",
			CAT_TRAVEL => "여행",
			// CAT_WIKIHOW => "WikiHow",
			CAT_WORK => "취업 및 직장생활",
			CAT_YOUTH => "청소년",
			);
}
elseif($wgLanguageCode == 'tr') {
	$wgCategoryNames = array(
			CAT_ARTS => 'Sanat ve Eğlence',
			CAT_CARS => 'Araba ve Diğer Araçlar',
			CAT_COMPUTERS => 'Bilgisayar ve Elektronik Cihazlar',
			CAT_EDUCATION => 'Eğitim ve İletişim',
			CAT_FAMILY => 'Aile Yaşamı',
			CAT_FINANCE => 'Finans ve Ticaret',
			CAT_FOOD => 'Yemek ve Davetler',
			CAT_HEALTH => 'Sağlık',
			CAT_HOBBIES => 'Hobi ve Beceriler',
			CAT_HOLIDAYS => 'Bayram ve Gelenekler',
			CAT_HOME => 'Ev ve Bahçe',
			CAT_PERSONAL => 'Kişisel Bakım ve Stil',
			CAT_PETS => 'Evcil ve Yabani Hayvanlar',
			CAT_PHILOSOPHY => 'Felsefe ve Din',
			CAT_RELATIONSHIPS => 'İlişkiler',
			CAT_SPORTS => 'Spor ve Fitness',
			CAT_TRAVEL => 'Seyahat',
			// CAT_WIKIHOW => 'wikiHow',
			CAT_WORK => 'İş Hayatı',
			CAT_YOUTH => 'Gençlik',
			);
}
else {
	$wgCategoryNames = $englishCategs;
}

