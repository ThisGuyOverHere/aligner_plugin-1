import React, {Component} from 'react';
import PropTypes from 'prop-types';

class ExportModalSendEmail extends Component {

    static propTypes = {
        sendEmailHandler: PropTypes.func.isRequired
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
                            onChange={this.sendToCloudHandler} />
                        <label className={ this.state.sendToCloud ? 'active' : 'inactive'}>Help to improve the public cloud</label>
                    </div>
                </div>
                <div className="actions">
                    <button className="send-btn ui button" tabIndex="3" type="button">
                        Send
                    </button>
                    <a href="javascript:void(0);" onClick={this.props.sendEmailHandler}> &lt; Go to cloud import</a>
                </div>


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
