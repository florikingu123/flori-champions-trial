<?php
include 'config.php';

// Create mini_mission_tasks table
$sql = "CREATE TABLE IF NOT EXISTS mini_mission_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mission_type VARCHAR(100) NOT NULL,
    task_title VARCHAR(255) NOT NULL,
    task_content TEXT NOT NULL,
    task_instructions TEXT,
    points_earned INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "mini_mission_tasks table created successfully!<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Insert 20 social share tasks
$social_tasks = [
    ['Share our upcoming "Community Cleanup Day" event on your social media', 'Event: Community Cleanup Day\nDate: This Saturday\nLocation: Central Park\nHelp us make our community cleaner! #VolunteerHub #CommunityService'],
    ['Share our "Food Drive for Families" event', 'Event: Food Drive for Families\nDate: Next Sunday\nLocation: Community Center\nHelp us feed families in need! #VolunteerHub #FoodDrive'],
    ['Share our "Plant Trees for Earth Day" campaign', 'Event: Plant Trees for Earth Day\nDate: April 22nd\nLocation: City Park\nHelp us plant 100 trees! #VolunteerHub #EarthDay'],
    ['Share our "Book Donation Drive" event', 'Event: Book Donation Drive\nDate: All month\nLocation: Library\nDonate books for children! #VolunteerHub #Education'],
    ['Share our "Senior Care Volunteer Day"', 'Event: Senior Care Volunteer Day\nDate: This Friday\nLocation: Senior Center\nSpend time with seniors! #VolunteerHub #SeniorCare'],
    ['Share our "Beach Cleanup Initiative"', 'Event: Beach Cleanup\nDate: This Saturday\nLocation: Main Beach\nHelp keep our beaches clean! #VolunteerHub #Environment'],
    ['Share our "Blood Donation Drive"', 'Event: Blood Donation Drive\nDate: Next Monday\nLocation: Hospital\nSave lives by donating blood! #VolunteerHub #Health'],
    ['Share our "Animal Shelter Volunteer Day"', 'Event: Animal Shelter Volunteer Day\nDate: This Sunday\nLocation: Local Shelter\nHelp care for animals! #VolunteerHub #Animals'],
    ['Share our "Holiday Food Basket Program"', 'Event: Holiday Food Baskets\nDate: December 15th\nLocation: Community Hall\nHelp families during holidays! #VolunteerHub #Holiday'],
    ['Share our "Youth Mentorship Program"', 'Event: Youth Mentorship Program\nDate: Ongoing\nLocation: Youth Center\nMentor young people! #VolunteerHub #Mentorship'],
    ['Share our "Disaster Relief Fundraiser"', 'Event: Disaster Relief Fundraiser\nDate: This Weekend\nLocation: Town Square\nHelp disaster victims! #VolunteerHub #Relief'],
    ['Share our "Elderly Tech Support Day"', 'Event: Tech Support for Seniors\nDate: Next Wednesday\nLocation: Community Center\nTeach seniors technology! #VolunteerHub #Tech'],
    ['Share our "Children\'s Hospital Visit"', 'Event: Hospital Visit for Kids\nDate: This Friday\nLocation: Children\'s Hospital\nBring joy to sick children! #VolunteerHub #Kids'],
    ['Share our "Homeless Shelter Meal Service"', 'Event: Meal Service\nDate: Every Sunday\nLocation: Homeless Shelter\nServe meals to those in need! #VolunteerHub #Meals'],
    ['Share our "Environmental Awareness Week"', 'Event: Environmental Awareness Week\nDate: Next Week\nLocation: Various Locations\nLearn about the environment! #VolunteerHub #Environment'],
    ['Share our "Community Garden Project"', 'Event: Community Garden\nDate: This Saturday\nLocation: Community Garden\nHelp grow fresh vegetables! #VolunteerHub #Garden'],
    ['Share our "Literacy Program for Adults"', 'Event: Adult Literacy Program\nDate: Every Tuesday\nLocation: Library\nTeach adults to read! #VolunteerHub #Education'],
    ['Share our "Veteran Support Event"', 'Event: Veteran Support Day\nDate: November 11th\nLocation: VFW Hall\nSupport our veterans! #VolunteerHub #Veterans'],
    ['Share our "Refugee Welcome Program"', 'Event: Refugee Welcome\nDate: Ongoing\nLocation: Community Center\nHelp refugees settle in! #VolunteerHub #Refugees'],
    ['Share our "Mental Health Awareness Campaign"', 'Event: Mental Health Awareness\nDate: All Month\nLocation: Online\nSpread awareness about mental health! #VolunteerHub #MentalHealth']
];

// Insert 20 translation tasks
$translation_tasks = [
    ['Translate to Spanish: "Join us for a community cleanup event this Saturday at Central Park. Help make our neighborhood cleaner!"', 'Spanish'],
    ['Translate to French: "We need volunteers for our food drive next Sunday. Help us feed families in need!"', 'French'],
    ['Translate to German: "Our tree planting event is on Earth Day. Come help us plant 100 trees in the city park!"', 'German'],
    ['Translate to Spanish: "Book donation drive is happening all month. Donate books for children at the library!"', 'Spanish'],
    ['Translate to French: "Senior care volunteer day is this Friday. Spend time with seniors at the senior center!"', 'French'],
    ['Translate to Italian: "Beach cleanup initiative this Saturday. Help keep our beaches clean and beautiful!"', 'Italian'],
    ['Translate to Portuguese: "Blood donation drive next Monday at the hospital. Save lives by donating blood!"', 'Portuguese'],
    ['Translate to Spanish: "Animal shelter volunteer day this Sunday. Help care for animals at the local shelter!"', 'Spanish'],
    ['Translate to French: "Holiday food basket program on December 15th. Help families during the holidays!"', 'French'],
    ['Translate to German: "Youth mentorship program is ongoing. Mentor young people at the youth center!"', 'German'],
    ['Translate to Spanish: "Disaster relief fundraiser this weekend. Help disaster victims in our community!"', 'Spanish'],
    ['Translate to Italian: "Elderly tech support day next Wednesday. Teach seniors how to use technology!"', 'Italian'],
    ['Translate to Portuguese: "Children\'s hospital visit this Friday. Bring joy to sick children!"', 'Portuguese'],
    ['Translate to Spanish: "Homeless shelter meal service every Sunday. Serve meals to those in need!"', 'Spanish'],
    ['Translate to French: "Environmental awareness week next week. Learn about protecting our environment!"', 'French'],
    ['Translate to German: "Community garden project this Saturday. Help grow fresh vegetables!"', 'German'],
    ['Translate to Spanish: "Adult literacy program every Tuesday. Teach adults how to read!"', 'Spanish'],
    ['Translate to Italian: "Veteran support event on November 11th. Support our veterans!"', 'Italian'],
    ['Translate to Portuguese: "Refugee welcome program is ongoing. Help refugees settle in our community!"', 'Portuguese'],
    ['Translate to Spanish: "Mental health awareness campaign all month. Spread awareness about mental health!"', 'Spanish']
];

// Insert 20 poster creation tasks
$poster_tasks = [
    ['Create a poster for "Community Cleanup Day" event on Saturday', 'Community Cleanup Day', 'This Saturday', 'Join us at Central Park to clean up our community!'],
    ['Create a poster for "Food Drive for Families" next Sunday', 'Food Drive for Families', 'Next Sunday', 'Help us feed families in need at the Community Center!'],
    ['Create a poster for "Plant Trees for Earth Day" on April 22nd', 'Plant Trees for Earth Day', 'April 22nd', 'Help us plant 100 trees in City Park!'],
    ['Create a poster for "Book Donation Drive" all month', 'Book Donation Drive', 'All Month', 'Donate books for children at the Library!'],
    ['Create a poster for "Senior Care Volunteer Day" this Friday', 'Senior Care Volunteer Day', 'This Friday', 'Spend time with seniors at the Senior Center!'],
    ['Create a poster for "Beach Cleanup Initiative" this Saturday', 'Beach Cleanup', 'This Saturday', 'Help keep our beaches clean at Main Beach!'],
    ['Create a poster for "Blood Donation Drive" next Monday', 'Blood Donation Drive', 'Next Monday', 'Save lives by donating blood at the Hospital!'],
    ['Create a poster for "Animal Shelter Volunteer Day" this Sunday', 'Animal Shelter Volunteer Day', 'This Sunday', 'Help care for animals at the Local Shelter!'],
    ['Create a poster for "Holiday Food Basket Program" on December 15th', 'Holiday Food Baskets', 'December 15th', 'Help families during holidays at Community Hall!'],
    ['Create a poster for "Youth Mentorship Program" ongoing', 'Youth Mentorship Program', 'Ongoing', 'Mentor young people at the Youth Center!'],
    ['Create a poster for "Disaster Relief Fundraiser" this weekend', 'Disaster Relief Fundraiser', 'This Weekend', 'Help disaster victims at Town Square!'],
    ['Create a poster for "Elderly Tech Support Day" next Wednesday', 'Tech Support for Seniors', 'Next Wednesday', 'Teach seniors technology at Community Center!'],
    ['Create a poster for "Children\'s Hospital Visit" this Friday', 'Hospital Visit for Kids', 'This Friday', 'Bring joy to sick children at Children\'s Hospital!'],
    ['Create a poster for "Homeless Shelter Meal Service" every Sunday', 'Meal Service', 'Every Sunday', 'Serve meals to those in need at Homeless Shelter!'],
    ['Create a poster for "Environmental Awareness Week" next week', 'Environmental Awareness Week', 'Next Week', 'Learn about the environment at various locations!'],
    ['Create a poster for "Community Garden Project" this Saturday', 'Community Garden', 'This Saturday', 'Help grow fresh vegetables at Community Garden!'],
    ['Create a poster for "Literacy Program for Adults" every Tuesday', 'Adult Literacy Program', 'Every Tuesday', 'Teach adults to read at the Library!'],
    ['Create a poster for "Veteran Support Event" on November 11th', 'Veteran Support Day', 'November 11th', 'Support our veterans at VFW Hall!'],
    ['Create a poster for "Refugee Welcome Program" ongoing', 'Refugee Welcome', 'Ongoing', 'Help refugees settle in at Community Center!'],
    ['Create a poster for "Mental Health Awareness Campaign" all month', 'Mental Health Awareness', 'All Month', 'Spread awareness about mental health online!']
];

// Insert 20 file creation tasks
$file_tasks = [
    ['Create a volunteer guide for "Community Cleanup Events"', 'guide', 'Community Cleanup Events', 'Include safety tips, what to bring, and what to expect'],
    ['Create an email template for "Recruiting Volunteers for Food Drive"', 'template', 'Recruiting Volunteers for Food Drive', 'Make it friendly and encouraging'],
    ['Create a checklist for "Tree Planting Event Preparation"', 'checklist', 'Tree Planting Event Preparation', 'Include all necessary items and steps'],
    ['Create an FAQ document for "Book Donation Drive"', 'faq', 'Book Donation Drive', 'Answer common questions about donating books'],
    ['Create training material for "Senior Care Volunteers"', 'training', 'Senior Care Volunteers', 'Include communication tips and safety guidelines'],
    ['Create a volunteer guide for "Beach Cleanup Safety"', 'guide', 'Beach Cleanup Safety', 'Include safety protocols and environmental tips'],
    ['Create an email template for "Blood Donation Reminder"', 'template', 'Blood Donation Reminder', 'Remind volunteers about upcoming blood drive'],
    ['Create a checklist for "Animal Shelter Volunteer Orientation"', 'checklist', 'Animal Shelter Volunteer Orientation', 'Include all orientation requirements'],
    ['Create an FAQ document for "Holiday Food Basket Program"', 'faq', 'Holiday Food Basket Program', 'Answer questions about food basket distribution'],
    ['Create training material for "Youth Mentorship Program"', 'training', 'Youth Mentorship Program', 'Include mentorship best practices'],
    ['Create a volunteer guide for "Disaster Relief Response"', 'guide', 'Disaster Relief Response', 'Include emergency protocols and safety measures'],
    ['Create an email template for "Tech Support Volunteer Recruitment"', 'template', 'Tech Support Volunteer Recruitment', 'Recruit tech-savvy volunteers'],
    ['Create a checklist for "Hospital Visit Preparation"', 'checklist', 'Hospital Visit Preparation', 'Include health requirements and items to bring'],
    ['Create an FAQ document for "Homeless Shelter Meal Service"', 'faq', 'Homeless Shelter Meal Service', 'Answer questions about meal service protocols'],
    ['Create training material for "Environmental Awareness"', 'training', 'Environmental Awareness', 'Include environmental facts and tips'],
    ['Create a volunteer guide for "Community Garden Maintenance"', 'guide', 'Community Garden Maintenance', 'Include gardening tips and maintenance schedule'],
    ['Create an email template for "Literacy Program Updates"', 'template', 'Literacy Program Updates', 'Update volunteers on program progress'],
    ['Create a checklist for "Veteran Support Event Setup"', 'checklist', 'Veteran Support Event Setup', 'Include all setup requirements'],
    ['Create an FAQ document for "Refugee Welcome Program"', 'faq', 'Refugee Welcome Program', 'Answer questions about helping refugees'],
    ['Create training material for "Mental Health Awareness"', 'training', 'Mental Health Awareness', 'Include mental health resources and support information']
];

// Insert tasks into database
$stmt = $conn->prepare("INSERT INTO mini_mission_tasks (mission_type, task_title, task_content, task_instructions, points_earned) VALUES (?, ?, ?, ?, ?)");

// Insert social share tasks
foreach ($social_tasks as $task) {
    $stmt->bind_param("ssssi", $type, $title, $content, $instructions, $points);
    $type = 'social_share';
    $title = $task[0];
    $content = $task[1];
    $instructions = 'Share this event on your social media platform';
    $points = 10;
    $stmt->execute();
}

// Insert translation tasks
foreach ($translation_tasks as $task) {
    $stmt->bind_param("ssssi", $type, $title, $content, $instructions, $points);
    $type = 'translation';
    $title = $task[0];
    $content = $task[0];
    $instructions = 'Translate the text to ' . $task[1];
    $points = 15;
    $stmt->execute();
}

// Insert poster tasks
foreach ($poster_tasks as $task) {
    $stmt->bind_param("ssssi", $type, $title, $content, $instructions, $points);
    $type = 'poster_creation';
    $title = $task[0];
    $content = json_encode(['title' => $task[1], 'date' => $task[2], 'description' => $task[3]]);
    $instructions = 'Create a poster for this event';
    $points = 20;
    $stmt->execute();
}

// Insert file creation tasks
foreach ($file_tasks as $task) {
    $stmt->bind_param("ssssi", $type, $title, $content, $instructions, $points);
    $type = 'file_creation';
    $title = $task[0];
    $content = json_encode(['type' => $task[1], 'topic' => $task[2], 'details' => $task[3]]);
    $instructions = 'Create a ' . $task[1] . ' about ' . $task[2];
    $points = 25;
    $stmt->execute();
}

$stmt->close();
echo "All tasks inserted successfully!<br>";
$conn->close();
?>

