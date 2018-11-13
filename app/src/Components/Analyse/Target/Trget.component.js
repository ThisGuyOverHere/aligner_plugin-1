import React, {Component} from 'react';

const TargetComponent = (props) => {
    return (
        <div className="info">
            <label>Target file:</label>
            {!props.totalTargetSegments ?
                <div>
                    <div className="segments segments-in-loading">
                        <div className="radio-container">
                            <div className="radio-loader"></div>
                        </div>
                        <div className="segments-info">
                            <p className="info-sx-text-loader"></p>
                        </div>
                    </div>
                    <div className="segments segments-in-loading">
                        <div className="radio-container">
                            <div className="radio-loader"></div>
                        </div>
                        <div className="segments-info">
                            <p className="info-sx-text-loader"></p>
                        </div>
                    </div>
                </div>
                :
                <div>
                    <h4>{props.targetLangFileName}</h4>
                    <label>{props.targetLang}</label>
                    <h2>{props.totalTargetSegments} segments</h2>
                </div>
            }
        </div>
    );
};

export default TargetComponent;