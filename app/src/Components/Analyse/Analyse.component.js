import React, {Component} from 'react';


class AnalyseComponent extends Component {
    static propTypes = {};

    constructor(props) {
        super(props);
        this.state = {
           progress : 50,
        }
    }

    render() {
        return (
            <div>
                <h1>Matecat's intelligence is currently alligning your files, please wait</h1>
                <div className="bar-container">
                    <div className='ui progress' data-percent={this.state.progress}>
                        <div className='bar' style={{width: this.state.progress + '%' }}>
                            <div className='progress'>{this.state.progress + '%'}</div>
                        </div>
                    </div>
                </div>
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
            </div>
        );
    }
}

export default AnalyseComponent;