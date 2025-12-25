import os
import re

files = [
    'tests/Feature/HomeControllerTest.php',
    'tests/Feature/LoginControllerTest.php',
    'tests/Feature/SocioControllerTest.php',
    'tests/Feature/StatisticsControllerTest.php',
    'tests/Integration/DatabaseSchemaTest.php',
    'tests/Integration/SecurityWorkflowTest.php',
    'tests/Performance/ExecutionTimeTest.php',
    'tests/Security/AccessControlTest.php',
    'tests/Security/AuditTrailTest.php',
    'tests/Unit/AmministratoreTest.php',
    'tests/Unit/OperatoreTest.php'
]

base_dir = r'c:/Program Files/Ampps/www/fratellanza-militare-archivio'

for file_path in files:
    full_path = os.path.join(base_dir, file_path)
    if not os.path.exists(full_path):
        print(f"File not found: {full_path}")
        continue

    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Regex patterns
    # Match beforeEach(function () {
    content = re.sub(
        r'(beforeEach\(function\s*\(\)\s*\{)',
        r'\1\n    /** @var \\Tests\\TestCase $this */',
        content
    )
    
    # Match test('...', function () {
    content = re.sub(
        r"(test\s*\((['\"].*?['\"])\s*,\s*function\s*\(\)\s*\{)",
        r'\1\n    /** @var \\Tests\\TestCase $this */',
        content
    )

    # Match it('...', function () {
    content = re.sub(
        r"(it\s*\((['\"].*?['\"])\s*,\s*function\s*\(\)\s*\{)",
        r'\1\n    /** @var \\Tests\\TestCase $this */',
        content
    )

    with open(full_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"Processed {file_path}")
