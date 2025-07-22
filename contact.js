/*
* リアルタイムバリデーションチェック
* フォームの各フィールドに対してリアルタイムでバリデーションを行い、エラーメッセージを表示します。
* 各フィールドの入力内容を検証し、問題がある場合はエラーメッセージを表示します。
* 入力が正しい場合はエラーメッセージをクリアします。
* ファイルアップロードの際は、ファイルサイズと形式をチェックします。
* フォーム送信時に全てのフィールドを検証し、問題がある場合は送信を防ぎます。
*/

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[name="form"]');
    if (!form) return;

    const nameInput = form.querySelector('input[name="name"]');
    const kanaInput = form.querySelector('input[name="kana"]');
    const postalInput = form.querySelector('input[name="postal_code"]');
    const prefectureInput = form.querySelector('input[name="prefecture"]');
    const cityInput = form.querySelector('input[name="city_town"]');
    const buildingInput = form.querySelector('input[name="building"]');
    const telInput = form.querySelector('input[name="tel"]');
    const emailInput = form.querySelector('input[name="email"]');
    const doc1Input = form.querySelector('input[name="document1"]');
    const doc2Input = form.querySelector('input[name="document2"]');

    const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB

    function showError(input, message, className = 'error-msg') {
        clearError(input, className);
        input.classList.add('error-form');
        const div = document.createElement('div');
        div.className = className;
        div.textContent = message;

        if (input.name === 'postal_code') {
            const wrapper = document.getElementById('postalWrapper');
            wrapper.appendChild(div); // ボタンの後ろに挿入
        } else {
            input.parentNode.insertBefore(div, input.nextSibling); // 通常の挿入位置
        }
        return false;
    }

    function clearError(input, className = 'error-msg') {
        input.classList.remove('error-form');

        if (input.name === 'postal_code') {
            const wrapper = document.getElementById('postalWrapper');
            const errors = wrapper.querySelectorAll(`.${className}`);
            errors.forEach(e => e.remove());

            return true;
        }

        const parent = input.parentNode;
        const errors = parent.querySelectorAll(`.${className}`);
        errors.forEach(err => err.remove());

        return true;
    }

    // 各フィールドのバリデーション関数
    // 名前、ふりがな、郵便番号、住所、電話番号、メールアドレス、ファイルアップロードのバリデーションを行います。
    // 各関数は入力値を検証し、問題がある場合はエラーメッセージを表示します。
    // 入力が正しい場合はエラーメッセージをクリアします。
    // それぞれの関数は、入力値を取得し、必要な検証を行います。
    // エラーメッセージは、入力フィールドの後ろに表示されます。
    // 入力値が空である場合、または形式が正しくない場合は、エラーメッセージを表示します。
    // 入力値が正しい場合は、エラーメッセージをクリアします。
    // これらの関数は、リアルタイムで入力値を検証するために、入力イベントや変更イベントにバインドされます。
    function validateName() {
        const val = nameInput.value;
        clearError(nameInput);
        const trimmed = val.replace(/[\s\u3000]+/g, '');
        if (!trimmed) return showError(nameInput, '名前が入力されていません');
        if (/^[\s\u3000]|[\s\u3000]$/.test(val)) return showError(nameInput, '名前の先頭または末尾にスペースを含めないでください');
        if (val.length > 20) return showError(nameInput, '名前は20文字以内で入力してください');
        return clearError(nameInput);
    }

    function validateKana() {
        const val = kanaInput.value;
        clearError(kanaInput);
        const trimmed = val.replace(/[\s\u3000]+/g, '');
        if (!trimmed) return showError(kanaInput, 'ふりがなが入力されていません');
        if (/^[\s\u3000]|[\s\u3000]$/.test(val)) return showError(kanaInput, 'ふりがなの先頭または末尾にスペースを含めないでください');
        if (val.length > 20) return showError(kanaInput, 'ふりがなは20文字以内で入力してください');
        if (!/^[ぁ-んー\s\u3000]+$/u.test(val)) return showError(kanaInput, 'ひらがなで入力してください');
        return clearError(kanaInput);
    }

    function validatePostal() {
        const val = postalInput.value;
        clearError(postalInput, 'error-msg2');
        if (!val) return showError(postalInput, '郵便番号が入力されていません', 'error-msg2');
        if (!/^\d{3}-\d{4}$/.test(val)) {
            return showError(postalInput, '郵便番号は「XXX-XXXX」の形式で入力してください', 'error-msg2');
        }
        return clearError(postalInput);
    }

    function validateAddress() {
        const pre = prefectureInput.value;
        const city = cityInput.value;
        const building = buildingInput.value;
        clearError(prefectureInput);
        clearError(cityInput);
        clearError(buildingInput);
        function markAddressInputs() {
            prefectureInput.classList.add('error-form');
            cityInput.classList.add('error-form');
        }
        if (!pre || !city) {
            markAddressInputs();
            return showError(buildingInput, '住所(都道府県もしくは市区町村・番地)が入力されていません');
        }
        if (pre.length > 10) {
            markAddressInputs();
            return showError(buildingInput, '都道府県は10文字以内で入力してください');
        }
        if (city.length > 50 || building.length > 50) {
            markAddressInputs();
            return showError(buildingInput, '市区町村・番地もしくは建物名は50文字以内で入力してください');
        }
        return clearError(buildingInput);
    }

    function validateTel() {
        const val = telInput.value;
        clearError(telInput);
        if (!val) return showError(telInput, '電話番号が入力されていません');
        if (!/^0\d{1,4}-\d{1,4}-\d{3,4}$/.test(val) || val.length < 12 || val.length > 13) {
            return showError(telInput, '電話番号は12~13桁(例:XXX-XXXX-XXXX)で正しく入力してください');
        }
        return clearError(telInput);
    }

    function validateEmail() {
        const val = emailInput.value;
        clearError(emailInput);
        if (!val) return showError(emailInput, 'メールアドレスが入力されていません');
        const re = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
        if (!re.test(val)) return showError(emailInput, '有効なメールアドレスを入力してください');
        return clearError(emailInput);
    }

    function validateDocument(input) {
        const file = input && input.files ? input.files[0] : null;
        clearError(input);
        if (!file) return clearError(input);
        if (file.size > MAX_FILE_SIZE) return showError(input, '2MB以上はアップロードできません');
        if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
            return showError(input, 'ファイル形式は PNG,JPEG,jpg のいずれかのみ許可されています');
        }
        return clearError(input);
    }

    // リアルタイムバリデーションの設定
    if (nameInput) nameInput.addEventListener('input', validateName);
    if (kanaInput) kanaInput.addEventListener('input', validateKana);
    if (postalInput) postalInput.addEventListener('input', validatePostal);
    if (prefectureInput) prefectureInput.addEventListener('input', validateAddress);
    if (cityInput) cityInput.addEventListener('input', validateAddress);
    if (buildingInput) buildingInput.addEventListener('input', validateAddress);
    if (telInput) telInput.addEventListener('input', validateTel);
    if (emailInput) emailInput.addEventListener('input', validateEmail);
    if (doc1Input) doc1Input.addEventListener('change', () => validateDocument(doc1Input));
    if (doc2Input) doc2Input.addEventListener('change', () => validateDocument(doc2Input));

    // フォーム送信時のバリデーションチェック
    form.addEventListener('submit', (e) => {
        let valid = true;
        if (nameInput && !validateName()) valid = false;
        if (kanaInput && !validateKana()) valid = false;
        if (postalInput && !validatePostal()) valid = false;
        if ((prefectureInput || cityInput) && !validateAddress()) valid = false;
        if (telInput && !validateTel()) valid = false;
        if (emailInput && !validateEmail()) valid = false;
        if (doc1Input && !validateDocument(doc1Input)) valid = false;
        if (doc2Input && !validateDocument(doc2Input)) valid = false;
        if (!valid) e.preventDefault();
    });
});