import React from 'react';

const TargetComponent = (props) => {
    return (
        <div className="info">
            <label>Target file:</label>
            <h4>{props.targetLangFileName}</h4>
            <label>{props.targetLang}</label>
            <h2>{props.totalTargetSegments} segments</h2>
        </div>
    );
};

export default TargetComponent;