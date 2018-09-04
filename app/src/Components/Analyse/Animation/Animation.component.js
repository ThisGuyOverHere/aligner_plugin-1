import React, {Component} from 'react';


class Animation extends Component {
    static propTypes = {};

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div className="animation-container">
                <div className="row">
                    <div className="sx">
                        <div className="segment">
                            <div className="text"></div>
                            <div className="text"></div>
                            <div className="text"></div>
                        </div>
                    </div>
                    <div className="dx">
                        <div className="segment">
                            <div className="text"></div>
                            <div className="text"></div>
                            <div className="text"></div>
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="sx">
                        <div className="segment">
                            <div className="text"></div>
                            <div className="text"></div>
                            <div className="text"></div>
                        </div>
                    </div>
                    <div className="dx">
                        <div className="segment">
                            <div className="text"></div>
                            <div className="text"></div>
                            <div className="text"></div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

export default Animation;