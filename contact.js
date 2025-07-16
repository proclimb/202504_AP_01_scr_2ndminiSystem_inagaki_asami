/**
 * 各項目の入力を行う
 */
function validate() {

    // 1.エラー有無の初期化(true:エラーなし、false：エラーあり)
    var flag = true;

    // 2.エラーメッセージを削除
    removeElementsByClass("error");
    removeClass("error-form");

    // 3.お名前の入力をチェック
    // 3-1.必須チェック
    if (document.edit.name.value == "") {
        errorElement(document.edit.name, "お名前が入力されていません");
        flag = false;
    }

    // 4.ふりがなの入力をチェック
    // 4-1.必須チェック
    if (document.edit.kana.value == "") {
        errorElement(document.edit.kana, "ふりがなが入力されていません");
        flag = false;
    } else {
        // 4-2.ひらがなのチェック
        if (!validateKana(document.edit.kana.value)) {
            errorElement(document.edit.kana, "ひらがなを入れて下さい");
            flag = false;
        }
    }

    // 郵便番号
    if (document.edit.postal_code.value === "") {
        errorElement(document.edit.postal_code, "郵便番号が入力されていません");
        flag = false;
    } else if (!/^\d{3}-\d{4}$/.test(document.edit.postal_code.value)) {
        errorElement(document.edit.postal_code, "郵便番号の形式が正しくありません（例: 123-4567）");
        flag = false;
    }

    // 住所（都道府県、市区町村）
    if (document.edit.prefecture.value === "") {
        errorElement(document.edit.prefecture, "都道府県が入力されていません");
        flag = false;
    }
    if (document.edit.city_town.value === "") {
        errorElement(document.edit.city_town, "市区町村が入力されていません");
        flag = false;
    }

    // 6.電話番号の入力をチェック
    // 6-1.必須チェック
    if (document.edit.tel.value == "") {
        errorElement(document.edit.tel, "電話番号が入力されていません");
        flag = false;
    } else {
        // 6-2.電話番号の長さをチェック
        if (!validateTel(document.edit.tel.value)) {
            errorElement(document.edit.tel, "電話番号が違います");
            flag = false;
        }
    }

    // 5.メールアドレスの入力をチェック
    // 5-1.必須チェック
    if (document.edit.email.value == "") {
        errorElement(document.edit.email, "メールアドレスが入力されていません");
        flag = false;
    } else {
        // 5-2.メールアドレスの形式をチェック
        if (!validateMail(document.edit.email.value)) {
            errorElement(document.edit.email, "メールアドレスが正しくありません");
            flag = false;
        }
    }



    // 7.エラーチェック
    if (flag) {
        document.edit.submit();
    }

    return false;
}


/**
 * エラーメッセージを表示する
 * @param {*} form メッセージを表示する項目
 * @param {*} msg 表示するエラーメッセージ
 */
var errorElement = function (form, msg) {

    // 1.項目タグに error-form のスタイルを適用させる
    form.className = "error-form";

    // 2.エラーメッセージの追加
    // 2-1.divタグの作成
    var newElement = document.createElement("div");

    // 2-2.error のスタイルを作成する
    newElement.className = "error";

    // 2-3.エラーメッセージのテキスト要素を作成する
    var newText = document.createTextNode(msg);

    // 2-4.2-1のdivタグに2-3のテキストを追加する
    newElement.appendChild(newText);

    // 2-5.項目タグの次の要素として、2-1のdivタグを追加する
    form.parentNode.insertBefore(newElement, form.nextSibling);
}


/**
 * エラーメッセージの削除
 *   className が、設定されている要素を全件取得し、タグごと削除する
 * @param {*} className 削除するスタイルのクラス名
 */
var removeElementsByClass = function (className) {

    // 1.html内から className の要素を全て取得する
    var elements = document.getElementsByClassName(className);
    while (elements.length > 0) {
        // 2.取得した全ての要素を削除する
        elements[0].parentNode.removeChild(elements[0]);
    }
}

/**
 * 適応スタイルの削除
 *   className を、要素から削除する
 *
 * @param {*} className
 */
var removeClass = function (className) {

    // 1.html内から className の要素を全て取得する
    var elements = document.getElementsByClassName(className);
    while (elements.length > 0) {
        // 2.取得した要素からclassName を削除する
        elements[0].className = "";
    }
}

/**
 * メールアドレスの書式チェック
 * @param {*} val チェックする文字列
 * @returns true：メールアドレス、false：メールアドレスではない
 */
var validateMail = function (val) {

    // メールアドレスの書式が以下であるか(*は、半角英数字と._-)
    // ***@***.***
    // ***.***@**.***
    // ***.***@**.**.***
    if (val.match(/^[A-Za-z0-9]{1}[A-Za-z0-9_.-]*@[A-Za-z0-9_.-]+.[A-Za-z0-9]+$/) == null) {
        return false;
    } else {
        return true;
    }
}

/**
 * 電話番号のチェック
 * @param {*} val チェックする文字列
 * @returns true：電話番号、false：電話番号ではない
 */
var validateTel = function (val) {

    // 半角数値と-(ハイフン)のみであるか
    if (val.match(/^[0-9]{2,4}-[0-9]{2,4}-[0-9]{3,4}$/) == null) {
        return false;
    } else {
        return true;
    }
}

/**
 * ひらがなのチェック
 * @param {*} val チェックする文字列
 * @returns true：ひらがなのみ、false：ひらがな以外の文字がある
 */
var validateKana = function (val) {

    // ひらがな(ぁ～ん)と長音のみであるか
    if (val.match(/^[ぁ-んー]+$/) == null) {
        return false;
    } else {
        return true;
    }
}


// contact.js

document.addEventListener("DOMContentLoaded", function () {
    const nameField = document.getElementById("nameField");
    const nameError = document.getElementById("nameError");

    if (nameField && nameError) {
        nameField.addEventListener("input", function () {
            if (this.value.trim() === "") {
                nameError.textContent = "お名前は必須です";
                this.classList.add("error-form");
            } else {
                nameError.textContent = "";
                this.classList.remove("error-form");
            }
        });
    }

    const kanaField = document.getElementById("kanaField");
    const kanaError = document.getElementById("kanaError");

    if (kanaField && kanaError) {
        kanaField.addEventListener("input", function () {
            if (this.value.trim() === "") {
                kanaError.textContent = "ふりがなは必須です";
                this.classList.add("error-form");
            } else {
                kanaError.textContent = "";
                this.classList.remove("error-form");
            }
        });
    }

    // 郵便番号
    const postalCodeField = document.getElementById("postalCodeField");
    const postalCodeError = document.getElementById("postalCodeError");
    if (postalCodeField && postalCodeError) {
        postalCodeField.addEventListener("input", function () {
            const val = this.value.trim();
            const pattern = /^\d{3}-\d{4}$/;
            if (val === "") {
                postalCodeError.textContent = "郵便番号は必須です";
                this.classList.add("error-form");
            } else if (!pattern.test(val)) {
                postalCodeError.textContent = "郵便番号の形式が正しくありません（例：123-4567）";
                this.classList.add("error-form");
            } else {
                postalCodeError.textContent = "";
                this.classList.remove("error-form");
            }
        });
    }

    // 住所（都道府県）
    const prefectureField = document.getElementById("prefecture");
    if (prefectureField) {
        prefectureField.addEventListener("input", function () {
            if (this.value.trim() === "") {
                this.classList.add("error-form");
            } else {
                this.classList.remove("error-form");
            }
        });
    }

    // 住所（市区町村・番地）
    const cityTownField = document.getElementById("city_town");
    if (cityTownField) {
        cityTownField.addEventListener("input", function () {
            if (this.value.trim() === "") {
                this.classList.add("error-form");
            } else {
                this.classList.remove("error-form");
            }
        });
    }

    // 電話番号
    const telField = document.querySelector("input[name='tel']");
    if (telField) {
        telField.addEventListener("input", function () {
            const val = this.value.trim();
            const pattern = /^[0-9]{2,4}-[0-9]{2,4}-[0-9]{3,4}$/;
            if (val === "") {
                this.classList.add("error-form");
            } else if (!pattern.test(val)) {
                this.classList.add("error-form");
            } else {
                this.classList.remove("error-form");
            }
        });
    }

    // メールアドレス
    const emailField = document.querySelector("input[name='email']");
    if (emailField) {
        emailField.addEventListener("input", function () {
            const val = this.value.trim();
            const pattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
            if (val === "") {
                this.classList.add("error-form");
            } else if (!pattern.test(val)) {
                this.classList.add("error-form");
            } else {
                this.classList.remove("error-form");
            }
        });
    }
});
