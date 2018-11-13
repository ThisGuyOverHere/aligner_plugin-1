import React, {Component} from 'react';

const SourceComponent = (props) => {
    return (
        <div className="info">
            <label>Source file:</label>
            {!props.totalSourceSegments ?
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
                    <h4>{props.sourceLangFileName}</h4>
                    <label>{props.sourceLang}</label>
                    <h2>{props.totalSourceSegments} segments</h2>
                </div>
            }
        </div>
    );
};

export default SourceComponent;