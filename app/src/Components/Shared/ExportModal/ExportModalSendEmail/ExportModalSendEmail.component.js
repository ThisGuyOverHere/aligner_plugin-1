import React, {Component} from 'react';

class ExportModalSendEmail extends Component {

    static propTypes = {

    };

    constructor(props) {
        super(props);
        this.state = {
            sendToCloud: false,
        };
    }

    render() {
        return (
            <div id="sender">
                <h1> Download your TMX </h1>
                <h3> A copy we’ll be shared in public cloud </h3>

                <div className="sender-content">
                    <input className="" type="text" tabIndex="0" placeholder="insert an email"/>
                    <p> We’ll send you an email when the file is ready </p>
                </div>

                <div className="selection">
                    <div className="ui toggle checkbox">
                        <input
                            type="checkbox" className="myActive" name="cloud"
                            readOnly=""  tabIndex="2" value={this.state.sendToCloud}
                            onChange={this.sendToCloudHandler}
                        ></input>
                        <label className={ this.state.sendToCloud ? 'active' : 'inactive'}>Help to improve the public cloud</label>
                    </div>
                </div>

                <button className="send-btn ui button" tabIndex="3" type="">
                   Send
                </button>

            </div>
        );
    }

    sendToCloudHandler = () => {
        this.setState({
            sendToCloud: !this.state.sendToCloud,
        });
    };

}
export default ExportModalSendEmail;