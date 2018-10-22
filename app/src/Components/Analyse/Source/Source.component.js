import React, {Component} from 'react';

const SourceComponent = (props) => {
    return (
        <div className="info">
            <label>Source file:</label>
            <h4>{props.sourceLangFileName}</h4>
            <label>{props.sourceLang}</label>
            <h2>{props.totalSourceSegments} segments</h2>
        </div>
    );
};

export default SourceComponent;