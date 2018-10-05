import {httpMergeSegments} from "../HttpRequests/Alignment.http";

/**
 *
 * @param text
 * @returns {string} with a centerd ellipsis
 */
export const textEllipsisCenter = (text) => {
    if(text.length > 30){
        return text.substr(0, 12) + '[...]' + text.substr(text.length-12, text.length);
    }
    return text;
};

/**
 *
 * @param name
 * @param surname
 * @returns {string} the initials of user name
 */
export const getUserInitials = (name, surname) => {
    return name.split(" ").map((n)=>n[0]).join("") + surname.split(" ").map((n)=>n[0]).join("") ;
};

/**
 *
 * @param email
 * @returns {boolean} true if the email is a valid one, false otherwise
 */
export const emailValidator = (email) => {
    let re = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    if( re.test(email.toLowerCase()) ) {
        return true;
    }else{
        return false;
    }
};


/**
 *
 * @param {Object} method
 * @param {Function} callback
 */
export const syncWithBackend = (method, callback) => {
    switch (method.action) {
        case 'merge':
            httpMergeSegments(method.data.jobID, method.data.jobPassword, {
                order: method.data.order,
                type: method.data.type
            }).then((response) => {
                setTimeout(()=>{callback()},3000)
            },(error)=>{
                console.log(error);
            });
            break
    }
};