import React, {Component} from 'react';
import {getUserInitials} from "../../../../Helpers/SystemUtils.helper";
import PropTypes from "prop-types";

class ExportModalLogged extends Component {

    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
    };

    constructor(props) {
        super(props);
        this.state = {
            cloudCheckBox: true

        };
    }

    render() {
        return (
            <div id="logged">
                <h1>Export TMX in private or public cloud</h1>
                <h3>No empty segment or hided row will be exported</h3>
                <div className="user-data">
                    <div className="ui logged label">
                        {getUserInitials(this.props.user.first_name, this.props.user.last_name)}
                    </div>
                    <div className="info">
                        <h3> {this.props.user.first_name} </h3>
                        <p>  {this.props.user.email} </p>
                    </div>
                </div>

                <div className="memories">

                    {[0,1,2].map( (element,index) => {
                        return <div className="memory" key={index}>
                            <div className="radio-container">
                                <input type="radio" className="hidden" name="memory" value="name" tabIndex="2"></input>
                            </div>
                            <div className="memory-info">
                                <p>MyPersonalTMX {index}</p>
                                <p>1WWMSOMXHS213242</p>
                            </div>
                        </div>
                    })}
                </div>

                <div className="line"></div>

                <div className="memory-input-container">
                    <div className="radio-container">
                        <input type="radio" className="hidden"name="memory" value="name" tabIndex="3"></input>
                    </div>
                    <div className="memory-input">
                        <input type="text" tabIndex="4" placeholder="Create new resource"></input>
                    </div>
                </div>

                <div className="selection">
                    <div className="ui checked toggle checkbox">
                        <input
                            type="checkbox" className="" name="cloud"
                            readOnly=""  tabIndex="5" value={this.state.cloudCheckBox}
                            onChange={this.cloudHandler}
                        ></input>
                        <label className={ this.state.sendToCloud ? 'active' : 'inactive'} >Help to improve the public cloud</label>
                    </div>
                </div>

                <button className="export-btn ui button" tabIndex="6" type="">
                     Export
                </button>
            </div>
        );
    }

    cloudHandler = () => {
        this.setState({
            cloudCheckBox: !this.state.cloudCheckBox,
        })
        console.log(this.state.cloudCheckBox);
    };

}

export default ExportModalLogged;